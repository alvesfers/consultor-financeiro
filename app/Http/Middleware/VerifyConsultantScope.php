<?php

namespace App\Http\Middleware;

use App\Models\Consultant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyConsultantScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $consultantId = $request->route('consultant');
        $user = $request->user();

        $consultant = Consultant::findOrFail($consultantId);

        // Admin → sempre permitido
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Consultor → só o próprio espaço
        if ($user->role === 'consultant' && $user->consultant?->id === $consultant->id) {
            return $next($request);
        }

        // Cliente → só se for cliente desse consultor
        if ($user->role === 'client' && $user->client?->consultant_id === $consultant->id) {
            return $next($request);
        }

        abort(403, 'Você não tem permissão para acessar este consultor.');
    }
}
