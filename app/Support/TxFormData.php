<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Subcategory;

class TxFormData
{
    public static function build(int $clientId): array
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->where(function ($q) use ($clientId) {
                $q->whereNull('client_id')->orWhere('client_id', $clientId);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'group_id']);

        $subcategories = Subcategory::query()
            ->where('is_active', true)
            ->where(function ($q) use ($clientId) {
                $q->whereNull('client_id')->orWhere('client_id', $clientId);
            })
            ->whereIn('category_id', $categories->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'name', 'category_id']);

        $subByCat = [];
        foreach ($subcategories as $s) {
            $subByCat[$s->category_id][] = ['id' => $s->id, 'name' => $s->name];
        }

        $catsExpense = $categories->whereIn('group_id', [5, 3])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values();

        $catsIncome = $categories->whereIn('group_id', [1, 2])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values();

        return [
            'categories' => $categories,
            'subcategories' => $subcategories,
            'categoriesByKind' => ['expense' => $catsExpense, 'income' => $catsIncome],
            'subcategoriesByCategory' => $subByCat,
        ];
    }
}
