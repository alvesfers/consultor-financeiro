<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|array  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Usuário não autenticado → redireciona para login
        if (!$user) {
            return redirect()->route('login');
        }

        // Se o usuário for admin, sempre libera
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Verifica se a role do usuário está entre as permitidas
        if (!in_array($user->role, $roles)) {
            // Opcional: retorna 403 ou redireciona
            abort(403, 'Você não tem permissão para acessar esta área.');
        }

        return $next($request);
    }
}
