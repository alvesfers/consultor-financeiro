<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class AiTransactionController extends Controller
{
    private string $apiBase = 'https://api.openai.com/v1';

    private function openaiJson(string $endpoint, array $payload): array
    {
        $res = Http::withToken(config('services.openai.key'))
            ->acceptJson()
            ->post($this->apiBase . $endpoint, $payload);

        if (!$res->successful()) {
            return [
                'error' => true,
                'status' => $res->status(),
                'body' => $res->json() ?: $res->body(),
            ];
        }
        return $res->json();
    }

    private function openaiMultipart(string $endpoint, array $multipart): array
    {
        $res = Http::withToken(config('services.openai.key'))
            ->asMultipart()
            ->post($this->apiBase . $endpoint, $multipart);

        if (!$res->successful()) {
            return [
                'error' => true,
                'status' => $res->status(),
                'body' => $res->json() ?: $res->body(),
            ];
        }
        return $res->json();
    }

    private function schema(): array
    {
        return [
            'type' => 'object',
            'required' => ['amount','type','confidence'],
            'properties' => [
                'amount' => ['type'=>'number'],
                'type'   => ['type'=>'string','enum'=>['expense','income','transfer','adjustment']],
                'merchant'=> ['type'=>'string','nullable'=>true],
                'method'  => ['type'=>'string','enum'=>['cash','debit','credit_card','pix','transfer','boleto','adjustment'],'nullable'=>true],
                'date'    => ['type'=>'string','format'=>'date-time','nullable'=>true],

                'account_id'     => ['type'=>'integer','nullable'=>true],
                'card_id'        => ['type'=>'integer','nullable'=>true],
                'category_id'    => ['type'=>'integer','nullable'=>true],
                'subcategory_id' => ['type'=>'integer','nullable'=>true],

                'installment_count' => ['type'=>'integer','minimum'=>1,'nullable'=>true],
                'installment_index' => ['type'=>'integer','minimum'=>1,'nullable'=>true],
                'notes' => ['type'=>'string','nullable'=>true],
                'needs' => ['type'=>'array','items'=>['type'=>'string']],
                'questions' => ['type'=>'array','items'=>['type'=>'string']],
                'confidence' => ['type'=>'number','minimum'=>0,'maximum'=>1],
            ]
        ];
    }

    private function buildSystem(string $mode, array $ctx): string
    {
        return
"Você é um assistente que extrai dados de transações pessoais em BRL e retorna APENAS JSON conforme schema.
- Se for crédito/parcelado → method='credit_card' e prefira card_id.
- Se PIX/débito → prefira account_id.
- IDs devem vir das listas abaixo; se não souber, use null e explique em 'needs'.
- Se houver parcelas, preencha installment_count e installment_index.
OPÇÕES:
ACCOUNTS: ".json_encode($ctx['accounts'] ?? [], JSON_UNESCAPED_UNICODE)."
CARDS: ".json_encode($ctx['cards'] ?? [], JSON_UNESCAPED_UNICODE)."
CATEGORIES: ".json_encode($ctx['categories'] ?? [], JSON_UNESCAPED_UNICODE)."
SUBCATEGORIES: ".json_encode($ctx['subcategories'] ?? [], JSON_UNESCAPED_UNICODE)."
MODO: {$mode}.";
    }

    private function structured(string $system, array|string $userContent, string $model='gpt-4o-mini'): array
    {
        $payload = [
            'model' => $model,
            'input' => [
                ['role'=>'system','content'=>$system],
                ['role'=>'user','content'=>$userContent],
            ],
            // 🆕 formato atualizado
            'text' => [
                'format' => 'json_schema',
                'json_schema' => [
                    'name' => 'TransactionExtraction',
                    'schema' => $this->schema(),
                ],
            ],
        ];

        $res = $this->openaiJson('/responses', $payload);

        if (isset($res['error'])) {
            return ['ok'=>false, 'error'=>$res];
        }

        $text = $res['output'][0]['content'][0]['text'] ?? '{}';
        if (!Str::startsWith($text, '{')) $text = '{}';
        $json = json_decode($text, true) ?? [];
        return ['ok'=>true,'data'=>$json,'raw'=>$res];
    }

    /** -------- TEXTO -------- */
    public function parseText(Request $req)
    {
        $data = $req->validate([
            'text' => ['required','string','max:4000'],
            'context' => ['required','array'],
        ]);

        $sys = $this->buildSystem('TEXTO', $data['context']);
        return response()->json($this->structured($sys, $data['text']));
    }

    /** -------- ÁUDIO -------- */
    public function parseAudio(Request $req)
    {
        $req->validate([
            'audio' => ['required','file','mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/webm,audio/wav'],
            'context' => ['required'],
        ]);

        $rawCtx = $req->input('context');
        $ctx = is_array($rawCtx) ? $rawCtx : (json_decode($rawCtx ?? '{}', true) ?: []);

        $transc = $this->openaiMultipart('/audio/transcriptions', [
            ['name'=>'model','contents'=>'whisper-1'],
            ['name'=>'language','contents'=>'pt'],
            ['name'=>'file','contents'=>fopen($req->file('audio')->getRealPath(),'r'),'filename'=>$req->file('audio')->getClientOriginalName()],
        ]);

        if (isset($transc['error'])) {
            return response()->json(['ok'=>false,'stage'=>'whisper','error'=>$transc], 422);
        }

        $text = $transc['text'] ?? '';
        $sys = $this->buildSystem('ÁUDIO->TEXTO', $ctx);
        return response()->json($this->structured($sys, $text));
    }

    /** -------- IMAGEM -------- */
    public function parseImage(Request $req)
    {
        $req->validate([
            'image' => ['required','file','mimetypes:image/jpeg,image/png,image/webp'],
            'context' => ['required'],
        ]);

        $rawCtx = $req->input('context');
        $ctx = is_array($rawCtx) ? $rawCtx : (json_decode($rawCtx ?? '{}', true) ?: []);

        $b64 = base64_encode(file_get_contents($req->file('image')->getRealPath()));
        $sys = $this->buildSystem('IMAGEM (RECIBO)', $ctx);

        $payload = [
            'model' => 'gpt-4o',
            'input' => [
                ['role'=>'system','content'=>$sys],
                ['role'=>'user','content'=>[
                    ['type'=>'input_text','text'=>'Leia o recibo e extraia os campos do schema. Considere R$ como BRL.'],
                    ['type'=>'input_image','image_url'=>"data:image/jpeg;base64,{$b64}"],
                ]],
            ],
            // 🆕 formato atualizado
            'text' => [
                'format' => 'json_schema',
                'json_schema' => [
                    'name' => 'TransactionExtraction',
                    'schema' => $this->schema(),
                ],
            ],
        ];

        $res = $this->openaiJson('/responses', $payload);

        if (isset($res['error'])) {
            return response()->json(['ok'=>false,'stage'=>'vision','error'=>$res], 422);
        }

        $text = $res['output'][0]['content'][0]['text'] ?? '{}';
        $json = json_decode($text, true) ?? [];
        return response()->json(['ok'=>true,'data'=>$json,'raw'=>$res]);
    }
}
