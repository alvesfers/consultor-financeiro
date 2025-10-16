@if (session('success'))
    <x-alert type="success">
        <span>{{ session('success') }}</span>
    </x-alert>
@endif

@if (session('error'))
    <x-alert type="error" :timeout="0">
        <span>{{ session('error') }}</span>
    </x-alert>
@endif

@if (session('warning'))
    <x-alert type="warning">
        <span>{{ session('warning') }}</span>
    </x-alert>
@endif

@if (session('info'))
    <x-alert type="info">
        <span>{{ session('info') }}</span>
    </x-alert>
@endif

@if ($errors->any())
    <x-alert type="error" :timeout="0">
        <div class="font-semibold">Não foi possível processar sua ação.</div>
        <ul class="list-disc ml-5 text-sm mt-1">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
