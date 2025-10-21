<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeminiTransactionController extends Controller
{
    private string $genBase = 'https://generativelanguage.googleapis.com/v1/models/';
    private string $genModel;
    private string $embedModel;

    public function __construct()
    {
        // Configure em config/services.php e .env
        $this->genModel   = config('services.gemini.model', 'gemini-2.5-flash');
        $this->embedModel = config('services.gemini.embed_model', 'text-embedding-004');
    }

    /* ================== Helpers ================== */

    /** Converte array|json-string -> array */
    private function safeJson($v): array
    {
        if (is_array($v)) return $v;
        if (is_string($v) && $v !== '') {
            $d = json_decode($v, true);
            return is_array($d) ? $d : [];
        }
        return [];
    }

    /** Normaliza texto para matching (sem acentos e só alfanum.) */
    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        if (function_exists('transliterator_transliterate')) {
            $s = transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC', $s);
        } elseif (function_exists('iconv')) {
            $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            if ($t !== false) $s = $t;
        }
        return preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9\s]/i', ' ', $s));
    }

    private function normAmount($val): ?float
    {
        if ($val === null || $val === '') return null;
        if (is_numeric($val)) return (float)$val;
        $s = str_replace(['.', ' '], ['', ''], (string)$val);
        $s = str_replace(',', '.', $s);
        return is_numeric($s) ? (float)$s : null;
    }

    /** Extrai o primeiro bloco JSON válido de um texto (tolerante a “lixo” antes/depois) */
    private function extractFirstJson(?string $text): ?array
    {
        if (!$text) return null;

        // 1) maior bloco { ... }
        if (preg_match('/\{[\s\S]*\}/', $text, $m)) {
            $try = json_decode($m[0], true);
            if (is_array($try)) return $try;
        }

        // 2) blocos com crase
        if (preg_match('/```json\s*([\s\S]*?)```/i', $text, $m2) ||
            preg_match('/```\s*([\s\S]*?)```/i', $text, $m2)) {
            $try = json_decode(trim($m2[1]), true);
            if (is_array($try)) return $try;
        }

        // 3) fallback
        $try = json_decode($text, true);
        return is_array($try) ? $try : null;
    }

    /* ================== Gemini API Callers ================== */

    private function geminiGenerate(array $body): array
    {
        $url = "{$this->genBase}{$this->genModel}:generateContent?key=" . config('services.gemini.key');

        try {
            $res = Http::asJson()
                ->timeout(30)
                ->retry(3, 300, throw: false)
                ->post($url, $body);

            if (!$res->successful()) {
                $error = $res->json() ?: $res->body();
                Log::error('Gemini generate error', [
                    'status' => $res->status(),
                    'error'  => $error,
                ]);
                return ['ok' => false, 'error' => $error];
            }

            $json = $res->json();
            $text = data_get($json, 'candidates.0.content.parts.0.text');

            return ['ok' => true, 'text' => $text, 'raw' => $json];
        } catch (\Throwable $e) {
            Log::error('Gemini generate exception', ['msg' => $e->getMessage()]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function geminiEmbed(string $text): ?array
    {
        try {
            $url = "{$this->genBase}{$this->embedModel}:embedContent?key=" . config('services.gemini.key');

            $res = Http::asJson()
                ->timeout(30)
                ->retry(3, 300, throw: false)
                ->post($url, [
                    'content' => [ 'parts' => [ ['text' => $text] ] ],
                ]);

            if (!$res->successful()) {
                Log::warning('Gemini embed error', [
                    'status' => $res->status(),
                    'body'   => $res->body(),
                ]);
                return null;
            }

            $j = $res->json();
            return data_get($j, 'embedding.value') ?? data_get($j, 'embedding.values');
        } catch (\Throwable $e) {
            Log::warning('Gemini embed exception', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    private function cosine(array $a, array $b): float
    {
        $dot = 0; $na = 0; $nb = 0;
        $n = min(count($a), count($b));
        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $na  += $a[$i] ** 2;
            $nb  += $b[$i] ** 2;
        }
        if ($na == 0 || $nb == 0) return 0.0;
        return $dot / (sqrt($na) * sqrt($nb));
    }

    /* ================== Contexto (DB) ================== */

    private function ctxFromDb(?int $clientId): array
    {
        $accounts = DB::table('accounts')
            ->select('id','name','bank_id')
            ->when($clientId, fn($q)=>$q->where('client_id',$clientId))
            ->get()->map(fn($a)=>[
                'id'=>(int)$a->id,
                'label'=> (string)$a->name,
                'bank'=> (string)($a->bank_id ?? ''),
            ])->values()->all();

        $cards = DB::table('cards')
            ->select('id','brand','last4','name')
            ->when($clientId, fn($q)=>$q->where('client_id',$clientId))
            ->get()->map(fn($c)=>[
                'id'=>(int)$c->id,
                'label'=> (string)($c->name ?? ''),
                'brand'=> strtolower((string)($c->brand ?? '')),
                'last4'=> (string)($c->last4 ?? ''),
            ])->values()->all();

        $categories = DB::table('categories')
            ->select('id','name','client_id')
            ->where(function($q)use($clientId){
                $q->whereNull('client_id');
                if ($clientId) $q->orWhere('client_id',$clientId);
            })
            ->where('is_active',1)
            ->orderBy('id')
            ->get()->map(fn($c)=>[
                'id'=>(int)$c->id,
                'label'=> (string)$c->name,
            ])->values()->all();

        $subcategories = DB::table('subcategories')
            ->select('id','category_id','name','client_id')
            ->where(function($q)use($clientId){
                $q->whereNull('client_id');
                if ($clientId) $q->orWhere('client_id',$clientId);
            })
            ->where('is_active',1)
            ->orderBy('id')
            ->get()->map(fn($s)=>[
                'id'=>(int)$s->id,
                'category_id'=>(int)$s->category_id,
                'label'=> (string)$s->name,
            ])->values()->all();

        return compact('accounts','cards','categories','subcategories');
    }

    private function mergeCtx(array $a, array $b): array
    {
        $out = $a;
        foreach (['accounts','cards','categories','subcategories'] as $k) {
            if (empty($out[$k]) && !empty($b[$k])) $out[$k] = $b[$k];
        }
        return $out;
    }

    private function buildPrompt(string $mode, array $ctx, string $input): string
    {
        $cards = array_map(fn($c)=>[
            'id'=>$c['id'],'brand'=>$c['brand'],'last4'=>$c['last4'],'label'=>$c['label']
        ], $ctx['cards'] ?? []);
        $accs  = array_map(fn($a)=>['id'=>$a['id'],'label'=>$a['label']], $ctx['accounts'] ?? []);
        $cats  = array_map(fn($c)=>['id'=>$c['id'],'label'=>$c['label']], $ctx['categories'] ?? []);
        $subs  = array_map(fn($s)=>['id'=>$s['id'],'label'=>$s['label'],'category_id'=>$s['category_id']], $ctx['subcategories'] ?? []);

        $cardsJson = json_encode($cards, JSON_UNESCAPED_UNICODE);
        $accsJson  = json_encode($accs,  JSON_UNESCAPED_UNICODE);
        $catsJson  = json_encode($cats,  JSON_UNESCAPED_UNICODE);
        $subsJson  = json_encode($subs,  JSON_UNESCAPED_UNICODE);

        return <<<EOT
Você é um extrator de transações pessoais (BRL). Retorne **APENAS JSON** com:

{
  "amount": number,
  "type": "expense|income|transfer|adjustment",
  "merchant": string|null,
  "method": "pix|debit|credit_card|cash|transfer|boleto|adjustment"|null,
  "account_id": number|null,
  "card_id": number|null,
  "category_id": number|null,
  "subcategory_id": number|null,
  "installment_count": number|null,
  "installment_index": number|null,
  "date": string|null, // ISO 8601
  "notes": string|null
}

REGRAS:
- Converta "172,84" -> 172.84 (número).
- Se houver cartão (Visa/Master, "••6982" ou "6982"): method="credit_card" e selecione card_id pelo **last4** e/ou **brand** da lista.
- Escolha **sempre** a categoria/subcategoria **mais semelhante semânticamente** ao texto (nome do estabelecimento + descrição), entre as listas abaixo. Retorne **os IDs exatos**. Se não houver correspondência razoável, retorne null.

LISTAS:
CARDS: $cardsJson
ACCOUNTS: $accsJson
CATEGORIES: $catsJson
SUBCATEGORIES: $subsJson

MODO: $mode

ENTRADA:
$input
EOT;
    }

    /* ================== Heurísticas (método/cartão) ================== */

    private function fillCardByText(array $r, array $ctx, string $text): array
    {
        $hay = mb_strtolower($text.' '.($r['notes'] ?? '').' '.($r['merchant'] ?? ''));

        if (empty($r['method'])) {
            if (preg_match('/\b(pix)\b/u', $hay)) $r['method'] = 'pix';
            elseif (preg_match('/\b(deb(i|í)to)\b/u', $hay)) $r['method'] = 'debit';
            elseif (preg_match('/\b(credito|crédito)\b/u', $hay) ||
                    preg_match('/\d{4}/', $hay) ||
                    str_contains($hay, 'visa') || str_contains($hay, 'master')) {
                $r['method'] = 'credit_card';
            }
        }

        if (($r['method'] ?? null) !== 'credit_card') return $r;

        // por last4
        if (empty($r['card_id'])) {
            if (preg_match('/(?:\x{2022}|•|\*)+\s*(\d{4})\b/u', $hay, $m) || preg_match('/\b(\d{4})\b/u', $hay, $m2)) {

                $last4 = ($m[1] ?? $m2[1] ?? null);
                if ($last4) {
                    foreach ($ctx['cards'] as $c) {
                        if ($c['last4'] === (string)$last4) { $r['card_id'] = (int)$c->id; break; }
                    }
                }
            }
        }
        // por brand
        if (empty($r['card_id'])) {
            $brand = null;
            if (str_contains($hay, 'visa')) $brand = 'visa';
            elseif (str_contains($hay, 'master')) $brand = 'master';
            if ($brand) {
                foreach ($ctx['cards'] as $c) {
                    if ($c['brand'] === $brand) { $r['card_id'] = (int)$c['id']; break; }
                }
            }
        }
        return $r;
    }

    /* ================== Matching semântico (embeddings) ================== */

    private function pickByEmbeddings(string $qText, array $ctx): array
    {
        $qVec = $this->geminiEmbed($qText);
        if (!$qVec) return ['category_id'=>null,'subcategory_id'=>null];

        $catKey = 'emb:cats:'.md5($this->embedModel.':'.count($ctx['categories'] ?? []));
        $subKey = 'emb:subs:'.md5($this->embedModel.':'.count($ctx['subcategories'] ?? []));

        $catVecs = Cache::remember($catKey, 86400, function() use ($ctx){
            $arr = [];
            foreach ($ctx['categories'] as $c) {
                $vec = $this->geminiEmbed($c['label']);
                if ($vec) $arr[$c['id']] = $vec;
            }
            return $arr;
        });

        $subVecs = Cache::remember($subKey, 86400, function() use ($ctx){
            $arr = [];
            $categories = collect($ctx['categories']);
            foreach ($ctx['subcategories'] as $s) {
                $parent = $categories->firstWhere('id', $s['category_id'])['label'] ?? '';
                $vec = $this->geminiEmbed(trim($parent.' '.$s['label']));
                if ($vec) $arr[$s['id']] = $vec;
            }
            return $arr;
        });

        $bestCat = null; $bestCatScore = -1;
        foreach ($catVecs as $id=>$v) {
            $sc = $this->cosine($qVec, $v);
            if ($sc > $bestCatScore) { $bestCatScore=$sc; $bestCat=$id; }
        }

        $bestSub = null; $bestSubScore = -1; $bestSubCatId = null;
        foreach ($ctx['subcategories'] as $s) {
            if (!isset($subVecs[$s['id']])) continue;
            $sc = $this->cosine($qVec, $subVecs[$s['id']]);
            if ($sc > $bestSubScore) { $bestSubScore=$sc; $bestSub=$s['id']; $bestSubCatId=$s['category_id']; }
        }

        if ($bestSubScore >= 0.75) {
            return ['category_id'=>$bestSubCatId, 'subcategory_id'=>$bestSub];
        }
        if ($bestCatScore >= 0.65) {
            return ['category_id'=>$bestCat, 'subcategory_id'=>null];
        }
        return ['category_id'=>null,'subcategory_id'=>null];
    }

    /**
     * Aplica normalização e heurísticas; usa embeddings como fallback.
     * Também adapta ausência de coluna "merchant" -> usa notes preferencialmente.
     */
    private function enrichResult(?array $r, array $ctx, string $llmText): array
    {
        $r = $r ?? [];
        if (isset($r['amount'])) $r['amount'] = $this->normAmount($r['amount']);
        $r['type'] = $r['type'] ?? 'expense';

        // Se IA retornou merchant mas não há notes, reaproveita merchant em notes
        if (!empty($r['merchant']) && empty($r['notes'])) {
            $r['notes'] = $r['merchant'];
        }

        // Heurísticas de método/cartão
        $r = $this->fillCardByText($r, $ctx, $llmText);

        // Fallback semântico (prioriza notes; se não houver, usa merchant)
        if (empty($r['category_id']) && empty($r['subcategory_id'])) {
            $query = trim(($r['notes'] ?? $r['merchant'] ?? '').' '.$llmText);
            $picked = $this->pickByEmbeddings($query, $ctx);
            $r['category_id']    = $r['category_id']    ?? $picked['category_id'];
            $r['subcategory_id'] = $r['subcategory_id'] ?? $picked['subcategory_id'];
        }

        // Inteiros onde couber
        if (array_key_exists('category_id', $r) && $r['category_id'] !== null)       $r['category_id']    = (int)$r['category_id'];
        if (array_key_exists('subcategory_id', $r) && $r['subcategory_id'] !== null) $r['subcategory_id'] = (int)$r['subcategory_id'];
        if (array_key_exists('card_id', $r) && $r['card_id'] !== null)               $r['card_id']        = (int)$r['card_id'];
        if (array_key_exists('account_id', $r) && $r['account_id'] !== null)         $r['account_id']     = (int)$r['account_id'];

        return $r;
    }

    /* ================== Endpoints ================== */

    /** Processa texto livre (digitado) com Gemini. */
    public function parseText(Request $req)
    {
        $data = $req->validate([
            'text'      => 'required|string|max:4000',
            'client_id' => 'nullable|integer',
            'context'   => 'nullable', // pode vir string JSON ou array
        ]);

        $ctxDb = $this->ctxFromDb($data['client_id'] ?? null);
        $ctxRq = $this->safeJson($data['context'] ?? []);
        $ctx   = $this->mergeCtx($ctxDb, $ctxRq);

        $prompt = $this->buildPrompt('TEXTO', $ctx, $data['text']);

        $res = $this->geminiGenerate([
            'contents' => [[ 'role'=>'user', 'parts'=>[['text'=>$prompt]] ]],
        ]);

        $parsed = $this->extractFirstJson($res['text'] ?? null);

        // Usa o próprio input do usuário como base para heurísticas
        $final = $this->enrichResult($parsed, $ctx, $data['text']);

        return response()->json([
            'ok'     => $res['ok'],
            'data'   => $final,
            'raw'    => $res,
            'prompt' => $prompt,
        ]);
    }

    /** Processa imagem (recibo) com Gemini Vision. */
    public function parseImage(Request $req)
    {
        $data = $req->validate([
            'image'     => 'required|file|mimetypes:image/jpeg,image/png,image/webp',
            'client_id' => 'nullable|integer',
            'context'   => 'nullable', // string JSON ou array
        ]);

        $ctxDb = $this->ctxFromDb($data['client_id'] ?? null);
        $ctxRq = $this->safeJson($req->input('context'));
        $ctx   = $this->mergeCtx($ctxDb, $ctxRq);

        // Converte a imagem para Base64
        $b64 = base64_encode(file_get_contents($req->file('image')->getRealPath()));
        $mimeType = $req->file('image')->getMimeType();

        $prompt = $this->buildPrompt('IMAGEM (RECIBO)', $ctx, 'Leia o recibo e extraia os dados. Priorize valor, data e nome do estabelecimento.');

        $res = $this->geminiGenerate([
            'contents' => [[
                'role'=>'user',
                'parts'=>[
                    ['text'=>$prompt],
                    ['inline_data'=>[
                        'mime_type'=>$mimeType,
                        'data'=>$b64,
                    ]],
                ],
            ]],
        ]);

        $parsed = $this->extractFirstJson($res['text'] ?? null);

        // Para heurísticas, prioriza notes; se não houver, usa merchant
        $source = trim(($parsed['notes'] ?? $parsed['merchant'] ?? ''));
        $final  = $this->enrichResult($parsed, $ctx, $source);

        return response()->json([
            'ok'     => $res['ok'],
            'data'   => $final,
            'raw'    => $res,
            'prompt' => $prompt,
        ]);
    }
}
