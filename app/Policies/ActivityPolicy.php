<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Activity $activity): bool
    {
        if ($user->isAdmin() || $user->isPimpinan() || $user->isVerifier()) {
            return true;
        }

        return $user->isPPTK() && ($activity->person_in_charge_id === $user->id || $activity->unit_id === $user->unit_id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isPPTK();
    }

    public function update(User $user, Activity $activity): bool
    {
        if ($activity->isClosedOrLocked() || $activity->status === 'cancelled') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && ($activity->person_in_charge_id === $user->id || $activity->unit_id === $user->unit_id);
    }

    public function delete(User $user, Activity $activity): bool
    {
        if ($activity->isClosedOrLocked() || $activity->status === 'cancelled' || $activity->budgetPlans()->exists() || $activity->realizations()->exists()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && ($activity->person_in_charge_id === $user->id || $activity->unit_id === $user->unit_id);
    }

    public function changeStatus(User $user, Activity $activity): bool
    {
        if ($activity->budgetYear->is_closed || $activity->status === 'completed' || $activity->status === 'cancelled') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && ($activity->person_in_charge_id === $user->id || $activity->unit_id === $user->unit_id);
    }

    public function manageBudgetPlan(User $user, Activity $activity): bool
    {
        if ($activity->isClosedOrLocked() || $activity->status === 'cancelled') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && ($activity->person_in_charge_id === $user->id || $activity->unit_id === $user->unit_id);
    }

    public function startExecution(User $user, Activity $activity): bool
    {
        if ($activity->budgetYear->is_closed || $activity->status !== 'planned') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && ($activity->person_in_charge_id === $user->id || $activity->unit_id === $user->unit_id);
    }

    public function updateProgress(User $user, Activity $activity): bool
    {
        if ($activity->isClosedOrLocked() || ! in_array($activity->status, ['ongoing', 'revision'])) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && ($activity->person_in_charge_id === $user->id || $activity->unit_id === $user->unit_id);
    }

    public function uploadDocument(User $user, Activity $activity): bool
    {
        if ($activity->isClosedOrLocked() || $activity->status === 'cancelled') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && ($activity->person_in_charge_id === $user->id || $activity->unit_id === $user->unit_id);
    }

    public function submitForVerification(User $user, Activity $activity): bool
    {
        if ($activity->isClosedOrLocked() || ! in_array($activity->status, ['ongoing', 'revision'])) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && ($activity->person_in_charge_id === $user->id || $activity->unit_id === $user->unit_id);
    }

    public function startReview(User $user, Activity $activity): bool
    {
        return $user->isVerifier() && $activity->status === 'waiting_verification';
    }

    public function requestRevision(User $user, Activity $activity): bool
    {
        return $user->isVerifier() && $activity->status === 'waiting_verification';
    }

    public function rejectSubmission(User $user, Activity $activity): bool
    {
        return $user->isVerifier() && $activity->status === 'waiting_verification';
    }

    public function closeActivity(User $user, Activity $activity): bool
    {
        return $user->isVerifier() && $activity->status === 'waiting_verification';
    }
}
