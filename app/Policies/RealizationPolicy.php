<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\Realization;
use App\Models\User;

class RealizationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Realization $realization): bool
    {
        if ($user->isAdmin() || $user->isPimpinan() || $user->isVerifier()) {
            return true;
        }

        return $user->isPPTK() && ($realization->activity->unit_id === $user->unit_id || $realization->activity->person_in_charge_id === $user->id);
    }

    public function create(User $user, Activity $activity): bool
    {
        if ($activity->budgetYear->is_closed || $activity->status !== 'ongoing') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && $activity->person_in_charge_id === $user->id;
    }

    public function update(User $user, Realization $realization): bool
    {
        $activity = $realization->activity;

        if ($activity->budgetYear->is_closed || $activity->status === 'completed' || ! in_array($realization->status, ['draft', 'revision'])) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && $activity->person_in_charge_id === $user->id;
    }

    public function delete(User $user, Realization $realization): bool
    {
        $activity = $realization->activity;

        if ($activity->budgetYear->is_closed || $activity->status === 'completed' || $realization->status !== 'draft') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && $activity->person_in_charge_id === $user->id;
    }

    public function submit(User $user, Realization $realization): bool
    {
        $activity = $realization->activity;

        if ($activity->budgetYear->is_closed || $activity->status === 'completed' || ! in_array($realization->status, ['draft', 'revision'])) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && $activity->person_in_charge_id === $user->id;
    }

    public function verify(User $user, Realization $realization): bool
    {
        $activity = $realization->activity;

        if ($activity->budgetYear->is_closed || $activity->status === 'completed') {
            return false;
        }

        return $user->isVerifier();
    }
}
