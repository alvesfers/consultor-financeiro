<?php

if (! function_exists('dashboard_url')) {
    function dashboard_url(): string
    {
        $user = auth()->user();
        if (! $user) return url('/');

        return match ($user->role) {
            'admin' => route('admin.dashboard'),
            'consultant' => route('consultants.dashboard', ['consultant' => $user->consultant?->id]),
            'client' => route('client.dashboard'),
            default => url('/dashboard'),
        };
    }
}
