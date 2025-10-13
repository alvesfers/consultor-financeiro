@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold flex items-center gap-2">
                <i class="fa-regular fa-pen-to-square"></i> Editar categoria
            </h1>
            <a href="{{ route('consultant.categories.index', ['consultant' => $consultant]) }}" class="btn btn-ghost">
                <i class="fa-solid fa-arrow-left-long me-2"></i>Voltar
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success mb-4"><i class="fa-regular fa-circle-check"></i> {{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error mb-4">
                <i class="fa-regular fa-circle-xmark me-2"></i>
                <ul class="list-disc ms-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <form method="POST"
                    action="{{ route('consultant.categories.update', ['consultant' => $consultant, 'category' => $category->id]) }}"
                    class="space-y-3">
                    @csrf @method('PUT')

                    <div class="form-control">
                        <label class="label"><span class="label-text">Nome</span></label>
                        <input type="text" name="name" class="input input-bordered" required maxlength="120"
                            value="{{ old('name', $category->name) }}" />
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text">Categoria pai (opcional)</span></label>
                        <select name="parent_id" class="select select-bordered">
                            <option value="">— Sem pai —</option>
                            @foreach ($parents as $p)
                                <option value="{{ $p->id }}" @selected(old('parent_id', $category->parent_id) == $p->id)>{{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <label class="label cursor-pointer justify-start gap-3">
                        <input type="checkbox" name="is_active" class="toggle toggle-primary"
                            @checked(old('is_active', $category->is_active)) />
                        <span class="label-text">Ativa</span>
                    </label>

                    <div class="card-actions justify-end">
                        <button class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Salvar alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
