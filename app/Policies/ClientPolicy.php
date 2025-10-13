<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Admin libera geral antes de qualquer verificação.
     */
    public function before(User $user, string $ability)
    {
        if ($user->isAdmin()) {
            return true;
        }
    }

    /**
     * Qualquer consultor ou cliente pode listar (filtro aplicado no controller).
     */
    public function viewAny(User $user): bool
    {
        return $user->isConsultant() || $user->isClient();
    }

    /**
     * Visualização de um cliente específico.
     */
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

    /**
     * Atualização de dados do cliente.
     * Permitido apenas ao consultor responsável.
     */
    public function update(User $user, Client $client): bool
    {
        return $user->isConsultant()
            && $client->consultant_id === $user->consultant?->id;
    }

    /**
     * Exclusão segue a mesma regra de atualização.
     */
    public function delete(User $user, Client $client): bool
    {
        return $this->update($user, $client);
    }
}
