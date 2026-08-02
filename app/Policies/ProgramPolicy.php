<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Program $program): bool
    {
        if ($user->isAdmin() || $user->isPimpinan() || $user->isVerifier()) {
            return true;
        }

        return $user->isPPTK() && $user->unit_id === $program->unit_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isPPTK();
    }

    public function update(User $user, Program $program): bool
    {
        if ($program->budgetYear->is_closed) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && $user->unit_id === $program->unit_id;
    }

    public function delete(User $user, Program $program): bool
    {
        if ($program->budgetYear->is_closed || $program->activities()->exists()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && $user->unit_id === $program->unit_id;
    }
}
