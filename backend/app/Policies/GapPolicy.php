<?php

namespace App\Policies;

use App\Models\Gap;
use App\Models\User;

/**
 * Autorização no nível do REGISTRO.
 * O middleware "permission" responde "o perfil pode fazer isso?";
 * a Policy responde "pode fazer isso NESTE gap?" (ex.: setor do líder).
 */
class GapPolicy
{
    public function view(User $user, Gap $gap): bool
    {
        return $user->hasPermission('gaps.view') && $this->inScope($user, $gap);
    }

    public function update(User $user, Gap $gap): bool
    {
        return $user->hasPermission('gaps.update')
            || ($user->hasPermission('gaps.complement') && $this->inScope($user, $gap));
    }

    public function delete(User $user, Gap $gap): bool
    {
        return $user->isAdmin();
    }

    public function comment(User $user, Gap $gap): bool
    {
        return $user->hasPermission('comments.create') && $this->inScope($user, $gap);
    }

    public function attach(User $user, Gap $gap): bool
    {
        return $user->hasPermission('attachments.create') && $this->inScope($user, $gap);
    }

    /** Somente o setor solicitante (ou admin) valida a solução. */
    public function decideHomologation(User $user, Gap $gap): bool
    {
        return $user->isAdmin()
            || ($user->hasPermission('gaps.homologation.decide') && $user->sector_id === $gap->sector_id)
            || $user->id === $gap->sector_owner_id;
    }

    /** TI movimenta qualquer status; o líder só pode reabrir GAPs do seu setor. */
    public function transition(User $user, Gap $gap, ?string $target = null): bool
    {
        if ($user->hasPermission('gaps.transition')) {
            return true;
        }

        return $target === 'reaberto'
            && $user->hasPermission('gaps.reopen')
            && $this->inScope($user, $gap);
    }

    private function inScope(User $user, Gap $gap): bool
    {
        return ! $user->role->isSectorScoped() || $user->sector_id === $gap->sector_id;
    }
}
