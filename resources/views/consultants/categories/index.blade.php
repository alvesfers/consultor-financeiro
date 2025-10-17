@extends('layouts.app')

@section('content')
@php
    $chip = fn(bool $active) => $active
        ? '<span class="badge badge-success gap-1"><i class="fa-solid fa-circle-dot text-xs"></i> ativa</span>'
        : '<span class="badge badge-ghost gap-1"><i class="fa-regular fa-circle text-xs"></i> inativa</span>';

    $initialFilters = [
        'q'        => $q ?? '',
        'group_id' => $groupId ?? '',
        'status'   => $status ?? 'all'
    ];
@endphp

{{-- Defina o componente ANTES do HTML para evitar "is not defined" --}}
<script>
    // Disponibiliza globalmente para o x-data
    window.categoryPage = (props) => ({
        // ===== state =====
        categories: props.initialCategories || [],
        groups: props.groups || [],
        consultantId: props.consultantId,
        filters: Object.assign({ q: '', group_id: '', status: 'all' }, props.initialFilters || {}),
        newCategory: { name: '', group_id: '' },

        // ===== lifecycle =====
        init() {
            // se quiser, carregue algo aqui
        },

        // ===== listagem (AJAX JSON) =====
        async loadData() {
            const params = new URLSearchParams(this.filters).toString();
            const res = await fetch(`/${this.consultantId}/categories?${params}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) { console.error('Falha ao carregar categorias'); return; }
            const json = await res.json();
            this.categories = json.categories || [];
        },

        // ===== categorias =====
        async addCategory() {
            if (!this.newCategory.name) { alert('Informe o nome.'); return; }
            const res = await fetch(`/${this.consultantId}/categories`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.newCategory)
            });
            if (!res.ok) { alert('Erro ao salvar.'); return; }
            const json = await res.json();
            this.categories.unshift(json.category);
            this.newCategory = { name: '', group_id: '' };
        },

        async editCategory(cat) {
            const newName = prompt('Novo nome da categoria:', cat.name);
            if (!newName || newName === cat.name) return;

            const res = await fetch(`/${this.consultantId}/categories/${cat.id}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: newName })
            });
            if (!res.ok) { alert('Erro ao atualizar.'); return; }
            const json = await res.json();
            Object.assign(cat, json.category);
        },

        async toggleCategory(cat) {
            const res = await fetch(`/${this.consultantId}/categories/${cat.id}/toggle`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            if (!res.ok) { alert('Erro ao atualizar status.'); return; }
            const json = await res.json();
            cat.is_active = json.is_active ? 1 : 0;
        },

        async deleteCategory(id) {
            if (!confirm('Excluir esta categoria?')) return;
            const res = await fetch(`/${this.consultantId}/categories/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            if (!res.ok) { alert('Erro ao excluir.'); return; }
            this.categories = this.categories.filter(c => c.id !== id);
        },

        // ===== subcategorias (mesma controller) =====
        async addSub(cat) {
            if (!cat.newSubName) return;
            const res = await fetch(`/${this.consultantId}/categories/${cat.id}/subcategories`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: cat.newSubName })
            });
            if (!res.ok) { alert('Erro ao adicionar subcategoria.'); return; }
            const json = await res.json();
            cat.subcategories = cat.subcategories || [];
            cat.subcategories.push(json.subcategory);
            cat.newSubName = '';
        },

        async editSub(cat, sub) {
            const newName = prompt('Novo nome da subcategoria:', sub.name);
            if (!newName || newName === sub.name) return;

            const res = await fetch(`/${this.consultantId}/categories/${cat.id}/subcategories/${sub.id}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: newName })
            });
            if (!res.ok) { alert('Erro ao atualizar subcategoria.'); return; }
            const json = await res.json();
            Object.assign(sub, json.subcategory);
        },

        async toggleSub(cat, sub) {
            const res = await fetch(`/${this.consultantId}/categories/${cat.id}/subcategories/${sub.id}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ is_active: !Boolean(sub.is_active) })
            });
            if (!res.ok) { alert('Erro ao atualizar status.'); return; }
            const json = await res.json();
            Object.assign(sub, json.subcategory);
        },

        async deleteSub(cat, sub) {
            if (!confirm('Excluir esta subcategoria?')) return;
            const res = await fetch(`/${this.consultantId}/categories/${cat.id}/subcategories/${sub.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            if (!res.ok) { alert('Erro ao excluir subcategoria.'); return; }
            cat.subcategories = (cat.subcategories || []).filter(s => s.id !== sub.id);
        }
    });
</script>

<div
    x-data="categoryPage({
        initialCategories: @json($categories),
        consultantId: @json($consultant),
        groups: @json($groups),
        initialFilters: @json($initialFilters)
    })"
    x-init="init()"
    class="space-y-6"
>
    {{-- HEADER + REFRESH --}}
    <div class="flex items-center justify-between mb-2">
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <i class="fa-solid fa-tags"></i> Categorias
        </h1>

        <button class="btn btn-sm btn-ghost" @click="loadData()">
            <i class="fa-solid fa-rotate-right me-2"></i> Atualizar
        </button>
    </div>

    {{-- FILTROS --}}
    <div class="card bg-base-100 border border-base-300 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Grupo --}}
            <div class="form-control">
                <label class="label"><span class="label-text">Grupo</span></label>
                <select x-model="filters.group_id" class="select select-bordered" @change="loadData()">
                    <option value="">Todos</option>
                    <template x-for="g in groups" :key="g.id">
                        <option :value="g.id" x-text="g.name"></option>
                    </template>
                </select>
            </div>

            {{-- Status --}}
            <div class="form-control">
                <label class="label"><span class="label-text">Status</span></label>
                <select x-model="filters.status" class="select select-bordered" @change="loadData()">
                    <option value="all">Todos</option>
                    <option value="active">Ativos</option>
                    <option value="inactive">Inativos</option>
                </select>
            </div>

            {{-- Busca --}}
            <div class="form-control">
                <label class="label"><span class="label-text">Buscar</span></label>
                <div class="join">
                    <input type="text" x-model.lazy="filters.q" placeholder="Nome da categoria..."
                        class="input input-bordered join-item w-full" @keyup.enter="loadData()" />
                    <button class="btn btn-ghost join-item" @click="loadData()">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- GRID DE CATEGORIAS --}}
    <template x-if="categories.length === 0">
        <div class="text-center text-base-content/60 py-10">Nenhuma categoria encontrada.</div>
    </template>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <template x-for="cat in categories" :key="cat.id">
            <div class="card bg-base-100 border border-base-300 flex flex-col">
                <div class="card-body p-4 flex-1">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h2 class="font-bold text-lg" x-text="cat.name"></h2>
                            <p class="text-xs opacity-70" x-text="cat.group?.name || '— sem grupo —'"></p>
                        </div>
                        <div class="flex gap-1 items-center">
                            <button class="btn btn-xs btn-ghost" @click="editCategory(cat)">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <button class="btn btn-xs btn-ghost" :class="cat.is_active ? '' : 'opacity-60'"
                                    @click="toggleCategory(cat)">
                                <i class="fa-solid fa-power-off"></i>
                            </button>
                            <button class="btn btn-xs btn-ghost text-error" @click="deleteCategory(cat.id)">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1 max-h-64 overflow-y-auto pr-1">
                        <template x-for="sub in cat.subcategories || []" :key="sub.id">
                            <div class="flex justify-between items-center bg-base-200/40 rounded px-2 py-1">
                                <span x-text="sub.name" class="truncate"></span>
                                <div class="flex gap-1">
                                    <button class="btn btn-xs btn-ghost" @click="editSub(cat, sub)">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <button class="btn btn-xs btn-ghost" :class="sub.is_active ? '' : 'opacity-60'"
                                            @click="toggleSub(cat, sub)">
                                        <i class="fa-solid fa-power-off"></i>
                                    </button>
                                    <button class="btn btn-xs btn-ghost text-error" @click="deleteSub(cat, sub)">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- nova subcategoria --}}
                <div class="border-t border-base-300 p-3 flex items-center gap-2">
                    <input type="text" placeholder="Nova subcategoria..."
                        x-model="cat.newSubName"
                        class="input input-sm input-bordered flex-1" />
                    <button class="btn btn-sm btn-primary" @click="addSub(cat)">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- CADASTRAR NOVA CATEGORIA --}}
    <div class="mt-6 card bg-base-100 border border-base-300 p-4">
        <h3 class="font-semibold mb-2 flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nova categoria
        </h3>
        <div class="flex flex-col md:flex-row gap-3">
            <input type="text" x-model="newCategory.name" placeholder="Nome da categoria"
                   maxlength="255" class="input input-bordered flex-1" />
            <select x-model="newCategory.group_id" class="select select-bordered flex-1">
                <option value="">— Sem grupo —</option>
                <template x-for="g in groups" :key="g.id">
                    <option :value="g.id" x-text="g.name"></option>
                </template>
            </select>
            <button class="btn btn-primary" @click="addCategory()">
                <i class="fa-solid fa-floppy-disk mr-2"></i>Salvar
            </button>
        </div>
    </div>
</div>
@endsection
