<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryGroup;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /* =========================
     * LISTAGEM / TELA ÚNICA
     * ========================= */
    public function index(Request $request, $consultant)
    {
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status', 'all'); // all|active|inactive
        $groupId = $request->get('group_id');

        $query = Category::query()
            ->with(['group:id,name'])
            ->withCount('subcategories');

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
        if (! empty($groupId)) {
            $query->where('group_id', (int) $groupId);
        }

        // carrega categorias e, para os cards, já traz subcategorias (ordenadas)
        $categories = $query
            ->orderByRaw('CASE WHEN group_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('group_id')
            ->orderBy('name')
            ->get(['id', 'name', 'group_id', 'is_active'])
            ->load(['subcategories' => function ($q) {
                $q->orderBy('name')->select(['id', 'category_id', 'name', 'is_active']);
            }]);

        $groups = CategoryGroup::orderBy('name')->get(['id', 'name']);

        $summary = [
            'total' => $categories->count(),
            'active' => $categories->where('is_active', true)->count(),
            'inactive' => $categories->where('is_active', false)->count(),
        ];

        // HTML normal (primeiro load) ou JSON (AJAX de filtros)
        if ($request->wantsJson()) {
            return response()->json([
                'categories' => $categories,
                'summary' => $summary,
            ]);
        }

        return view('consultants.categories.index', compact(
            'categories', 'groups', 'consultant', 'q', 'status', 'groupId', 'summary'
        ));
    }

    /* =========================
     * CATEGORIAS
     * ========================= */
    public function store(Request $request, $consultant)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'group_id' => ['nullable', Rule::exists('category_groups', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $cat = Category::create($data);

        return $request->wantsJson()
            ? response()->json(['ok' => true, 'category' => $cat->fresh(['group'])->loadCount('subcategories')])
            : redirect()->route('consultants.categories.index', ['consultant' => $consultant])
                ->with('success', 'Categoria criada com sucesso!');
    }

    public function update(Request $request, $consultant, Category $category)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'group_id' => ['nullable', Rule::exists('category_groups', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ]);
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $category->update($data);

        return $request->wantsJson()
            ? response()->json(['ok' => true, 'category' => $category->fresh(['group'])->loadCount('subcategories')])
            : redirect()->route('consultants.categories.index', ['consultant' => $consultant])
                ->with('success', 'Categoria atualizada!');
    }

    public function destroy(Request $request, $consultant, Category $category)
    {
        $category->delete();

        return $request->wantsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', 'Categoria excluída!');
    }

    public function toggle(Request $request, $consultant, Category $category)
    {
        $category->is_active = ! $category->is_active;
        $category->save();

        return $request->wantsJson()
            ? response()->json(['ok' => true, 'is_active' => (bool) $category->is_active])
            : back()->with('success', 'Status atualizado!');
    }

    /* =========================
     * SUBCATEGORIAS (na mesma controller)
     * ========================= */

    // POST /{consultant}/categories/{category}/subcategories
    public function subStore(Request $request, $consultant, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $sub = $category->subcategories()->create($data);

        return response()->json([
            'ok' => true,
            'subcategory' => $sub,
        ]);
    }

    // PUT /{consultant}/categories/{category}/subcategories/{subcategory}
    public function subUpdate(Request $request, $consultant, Category $category, Subcategory $subcategory)
    {
        // garante vínculo correto
        if ((int) $subcategory->category_id !== (int) $category->id) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $subcategory->update($data);

        return response()->json([
            'ok' => true,
            'subcategory' => $subcategory->fresh(),
        ]);
    }

    // DELETE /{consultant}/categories/{category}/subcategories/{subcategory}
    public function subDestroy(Request $request, $consultant, Category $category, Subcategory $subcategory)
    {
        if ((int) $subcategory->category_id !== (int) $category->id) {
            abort(404);
        }

        $subcategory->delete();

        return response()->json(['ok' => true]);
    }
}
