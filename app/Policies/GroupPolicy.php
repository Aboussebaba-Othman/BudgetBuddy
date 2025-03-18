<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GroupPolicy
{
    /**
     * Détermine si l'utilisateur peut voir la liste des groupes.
     */
    public function viewAny(User $user): bool
    {
        return true; // Tout utilisateur authentifié peut voir sa liste de groupes
    }

    /**
     * Détermine si l'utilisateur peut voir un groupe spécifique.
     */
    public function view(User $user, Group $group): bool
    {
        return $group->isMember($user->id);
    }

    /**
     * Détermine si l'utilisateur peut créer des groupes.
     */
    public function create(User $user): bool
    {
        return true; // Tout utilisateur authentifié peut créer un groupe
    }

    /**
     * Détermine si l'utilisateur peut mettre à jour un groupe.
     */
    public function update(User $user, Group $group): bool
    {
        return $group->isAdmin($user->id);
    }

    /**
     * Détermine si l'utilisateur peut supprimer un groupe.
     */
    public function delete(User $user, Group $group): bool
    {
        return $group->isAdmin($user->id);
    }

    /**
     * Détermine si l'utilisateur peut ajouter des membres à un groupe.
     */
    public function addMembers(User $user, Group $group): bool
    {
        return $group->isAdmin($user->id);
    }

    /**
     * Détermine si l'utilisateur peut supprimer des membres d'un groupe.
     */
    public function removeMembers(User $user, Group $group): bool
    {
        return $group->isAdmin($user->id);
    }
}