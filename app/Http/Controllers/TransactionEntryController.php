<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Card;
use App\Models\Category;
use App\Models\Subcategory;

class TransactionEntryController extends Controller
{
    /**
     * Exibe o formulário de criação de transação inteligente com os dados de contexto.
     */
    public function create(Request $request, $consultant)
    {
        // Ajuste para sua regra de cliente atual:
        $clientId = (int) (auth()->user()->client_id ?? 1);

        $accounts = Account::where('client_id', $clientId)
            ->select('id','name','bank_id')
            ->orderBy('name')
            ->get()
            ->map(fn($a)=>[
                'id' => (int)$a->id,
                'label' => trim($a->name),
                'aliases' => [trim($a->name)],
            ])->toArray();

        $cards = Card::where('client_id', $clientId)
            ->select('id','name','brand','last4','close_day','due_day','payment_account_id')
            ->orderBy('name')
            ->get()
            ->map(fn($c)=>[
                'id' => (int)$c->id,
                'label' => trim($c->name),
                'brand' => $c->brand,
                'last4' => $c->last4,
                'close_day' => (int) $c->close_day,
                'due_day'   => (int) $c->due_day,
                'payment_account_id' => $c->payment_account_id ? (int)$c->payment_account_id : null,
                'aliases' => array_values(array_filter([
                    trim($c->name),
                    $c->brand ? strtoupper($c->brand) : null,
                    $c->last4 ? "••••".$c->last4 : null,
                ])),
            ])->toArray();

        // Categories: globais (client_id NULL) OU do cliente
        $categories = Category::query()
            ->where(function ($q) use ($clientId) {
                $q->whereNull('client_id')
                ->orWhere('client_id', $clientId);
            })
            ->where('is_active', 1)
            ->select('id','name','group_id')
            ->orderBy('name')
            ->get()
            ->map(fn($cat) => [
                'id'       => (int) $cat->id,
                'label'    => trim($cat->name),
                'group_id' => (int) ($cat->group_id ?? 0),
                'aliases'  => [trim($cat->name)],
            ])->toArray();

        // Subcategories: globais (client_id NULL) OU do cliente
        $subcategories = Subcategory::query()
            ->where(function ($q) use ($clientId) {
                $q->whereNull('client_id')
                ->orWhere('client_id', $clientId);
            })
            ->where('is_active', 1)
            ->select('id','category_id','name')
            ->orderBy('name')
            ->get()
            ->map(fn($s) => [
                'id'          => (int) $s->id,
                'category_id' => (int) $s->category_id,
                'label'       => trim($s->name),
                'aliases'     => [trim($s->name)],
            ])->toArray();

        return view('transactions.create', [
            'consultantId'      => (int)$consultant,
            'clientId'          => $clientId,
            'accounts'          => $accounts,
            'cards'             => $cards,
            'categories'        => $categories,
            'subcategories'     => $subcategories,
        ]);
    }
}