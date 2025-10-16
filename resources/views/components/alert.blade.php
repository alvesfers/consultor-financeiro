@props([
    'type' => 'info', // success | error | warning | info
    'icon' => null, // ex: "fa-solid fa-circle-check" (se null, define pelo type)
    'timeout' => 4000, // ms; 0 ou null para não esconder automaticamente
    'fixed' => true, // true = toast flutuante; false = inline
    'position' => 'top-right', // top-right | top-center | bottom-right | bottom-left
])

@php
    $typeClass =
        [
            'success' => 'alert-success',
            'error' => 'alert-error',
            'warning' => 'alert-warning',
            'info' => 'alert-info',
        ][$type] ?? 'alert-info';

    $iconClass =
        $icon ??
        match ($type) {
            'success' => 'fa-solid fa-circle-check',
            'error' => 'fa-solid fa-triangle-exclamation',
            'warning' => 'fa-solid fa-circle-exclamation',
            default => 'fa-solid fa-circle-info',
        };

    $posClass = match ($position) {
        'top-right' => 'fixed top-4 right-4',
        'top-center' => 'fixed top-4 left-1/2 -translate-x-1/2',
        'bottom-right' => 'fixed bottom-4 right-4',
        'bottom-left' => 'fixed bottom-4 left-4',
        default => '',
    };

    $wrapperClasses = $fixed ? "$posClass z-50 w-auto max-w-sm" : '';
@endphp

<div x-data="{ show: true }"
    @if ($timeout) x-init="setTimeout(() => show = false, {{ (int) $timeout }})" @endif x-show="show"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-3"
    x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-3"
    class="{{ $wrapperClasses }}">
    <div role="alert" class="alert {{ $typeClass }} shadow-lg relative">
        <i class="{{ $iconClass }} text-lg"></i>

        <div class="flex-1">
            {{ $slot }}
        </div>

        <button @click="show = false" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" aria-label="Fechar"
            type="button">✕</button>
    </div>
</div>
