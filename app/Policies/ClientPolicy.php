<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    // Admin libera geral
    public function before(User $user, string $ability)
    {
        if ($user->isAdmin()) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        // Consultor e Cliente podem listar algo (vamos filtrar no controller/repo)
        return $user->isConsultant() || $user->isClient();
    }

    public function view(User $user, Client $client): bool
    {
        if ($user->isConsultant()) {
            return $client->consultant_id === $user->consultant?->id;
        }
        if ($user->isClient()) {
            return $client->user_id === $user->id;
        }

        return false;
    }

    public function update(User $user, Client $client): bool
    {
        // Só consultor dono (ou admin via before)
        return $user->isConsultant()
            && $client->consultant_id === $user->consultant?->id;
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->update($user, $client);
    }
}
