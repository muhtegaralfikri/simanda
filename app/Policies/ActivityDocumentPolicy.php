<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Models\User;

class ActivityDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ActivityDocument $document): bool
    {
        if ($user->isAdmin() || $user->isPimpinan() || $user->isVerifier()) {
            return true;
        }

        return $user->isPPTK() && ($document->activity->unit_id === $user->unit_id || $document->activity->person_in_charge_id === $user->id);
    }

    public function uploadDocument(User $user, Activity $activity): bool
    {
        if ($activity->budgetYear->is_closed || $activity->status === 'cancelled' || $activity->status === 'completed') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && $activity->person_in_charge_id === $user->id;
    }

    public function downloadDocument(User $user, ActivityDocument $document): bool
    {
        if ($user->isAdmin() || $user->isPimpinan() || $user->isVerifier()) {
            return true;
        }

        return $user->isPPTK() && ($document->activity->unit_id === $user->unit_id || $document->activity->person_in_charge_id === $user->id);
    }

    public function deleteDocument(User $user, ActivityDocument $document): bool
    {
        $activity = $document->activity;

        if ($activity->budgetYear->is_closed || $activity->status === 'cancelled' || $activity->status === 'completed' || $document->status !== 'uploaded') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPPTK() && $activity->person_in_charge_id === $user->id;
    }

    public function verifyDocument(User $user, ActivityDocument $document): bool
    {
        $activity = $document->activity;

        if ($activity->budgetYear->is_closed || $activity->status === 'completed') {
            return false;
        }

        return $user->isVerifier();
    }
}
