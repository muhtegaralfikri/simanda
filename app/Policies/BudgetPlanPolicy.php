<?php

namespace App\Policies;

use App\Models\BudgetPlan;
use App\Models\User;

class BudgetPlanPolicy
{
    public function create(User $user, $activity): bool
    {
        if ($activity->isClosedOrLocked()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && $activity->person_in_charge_id === $user->id;
    }

    public function update(User $user, BudgetPlan $budgetPlan): bool
    {
        $activity = $budgetPlan->activity;
        if ($activity->isClosedOrLocked()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && $activity->person_in_charge_id === $user->id;
    }

    public function delete(User $user, BudgetPlan $budgetPlan): bool
    {
        $activity = $budgetPlan->activity;
        if ($activity->isClosedOrLocked()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && $activity->person_in_charge_id === $user->id;
    }
}
