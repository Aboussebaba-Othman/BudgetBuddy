<?php

namespace App\Policies;

use App\Models\GroupExpense;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GroupExpensePolicy
{
    /**
     * Détermine si l'utilisateur peut voir la liste des dépenses d'un groupe.
     */
    public function viewAny(User $user, GroupExpense $expense): bool
    {
        return $expense->group->isMember($user->id);
    }

    /**
     * Détermine si l'utilisateur peut voir une dépense spécifique.
     */
    public function view(User $user, GroupExpense $expense): bool
    {
        return $expense->group->isMember($user->id);
    }

    /**
     * Détermine si l'utilisateur peut créer des dépenses dans un groupe.
     */
    public function create(User $user, GroupExpense $expense): bool
    {
        return $expense->group->isMember($user->id);
    }

    /**
     * Détermine si l'utilisateur peut mettre à jour une dépense.
     */
    public function update(User $user, GroupExpense $expense): bool
    {
        return $expense->created_by === $user->id || $expense->group->isAdmin($user->id);
    }

    /**
     * Détermine si l'utilisateur peut supprimer une dépense.
     */
    public function delete(User $user, GroupExpense $expense): bool
    {
        return $expense->created_by === $user->id || $expense->group->isAdmin($user->id);
    }
}