<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request, $consultant)
    {
        // lista hierárquica simples
        $query = Category::query()->orderBy('name');
        if ($request->filled('q')) {
            $q = trim($request->get('q'));
            $query->where('name', 'like', "%{$q}%");
        }
        $categories = $query->get();

        // pais p/ select de relacionamento
        $parents = Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);

        return view('consultants.categories.index', compact('categories', 'parents', 'consultant'));
    }

    public function store(Request $request, $consultant)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        Category::create($data);

        return back()->with('success', 'Categoria criada com sucesso!');
    }

    public function edit(Request $request, $consultant, Category $category)
    {
        $parents = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('name')->get(['id', 'name']);

        return view('consultants.categories.edit', compact('category', 'parents', 'consultant'));
    }

    public function update(Request $request, $consultant, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        // evita ser filho de si mesma
        if (isset($data['parent_id']) && (int) $data['parent_id'] === (int) $category->id) {
            unset($data['parent_id']);
        }

        $category->update($data);

        return redirect()->route('consultants.categories.index', ['consultant' => $consultant])
            ->with('success', 'Categoria atualizada!');
    }

    public function destroy(Request $request, $consultant, Category $category)
    {
        // se tiver filhas, você pode escolher bloquear ou reatribuir; aqui vamos permitir deletar em cascata se FK permitir
        $category->delete();

        return back()->with('success', 'Categoria excluída!');
    }

    public function toggle(Request $request, $consultant, Category $category)
    {
        $category->is_active = ! $category->is_active;
        $category->save();

        return back()->with('success', 'Status atualizado!');
    }
}
