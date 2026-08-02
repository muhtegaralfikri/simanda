<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Models\Realization;
use App\Models\SystemAlert;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeadlineAlertService
{
    public function generateAlerts(): array
    {
        $createdCount = 0;
        $resolvedCount = 0;

        $startDays = config('simanda.alerts.activity_start_days', 7);
        $endDays = config('simanda.alerts.activity_end_days', 7);
        $verifDays = config('simanda.alerts.verification_waiting_days', 3);
        $revDays = config('simanda.alerts.revision_waiting_days', 3);

        $today = now()->startOfDay();
        $adminUsers = User::where('role', 'admin')->get();
        $pimpinanUsers = User::where('role', 'pimpinan')->get();
        $verifierUsers = User::where('role', 'verifier')->get();

        // 1. Kegiatan Planned Segera Dimulai
        $startingActivities = Activity::where('status', 'planned')
            ->where('start_date', '<=', $today->copy()->addDays($startDays))
            ->where('start_date', '>=', $today)
            ->get();

        foreach ($startingActivities as $act) {
            $key = "act_start_{$act->id}_{$act->start_date->format('Ymd')}";
            $daysLeft = (int) $today->diffInDays($act->start_date, false);
            $msg = "Kegiatan {$act->activity_code} — {$act->activity_name} dijadwalkan mulai dalam {$daysLeft} hari ({$act->start_date->format('d/m/Y')}).";

            $recipients = collect([$act->personInCharge])->merge($adminUsers)->merge($pimpinanUsers)->filter();
            foreach ($recipients as $u) {
                $userKey = "{$key}_u{$u->id}";
                if (! SystemAlert::where('unique_key', $userKey)->exists()) {
                    SystemAlert::create([
                        'user_id' => $u->id,
                        'alert_type' => 'activity_starting_soon',
                        'severity' => 'info',
                        'subject_type' => Activity::class,
                        'subject_id' => $act->id,
                        'title' => 'Kegiatan Segera Dimulai',
                        'message' => $msg,
                        'action_url' => "/activities/{$act->id}",
                        'due_date' => $act->start_date,
                        'unique_key' => $userKey,
                    ]);
                    $createdCount++;
                }
            }
        }

        // 2. Kegiatan Ongoing Mendekati Tenggat & Terlambat
        $ongoingActivities = Activity::whereNotIn('status', ['completed', 'cancelled'])->get();
        foreach ($ongoingActivities as $act) {
            if (! $act->end_date) {
                continue;
            }

            if ($act->end_date < $today) {
                // OVERDUE
                $daysLate = (int) $act->end_date->diffInDays($today);
                $key = "act_overdue_{$act->id}_{$act->end_date->format('Ymd')}";
                $msg = "PERINGATAN: Kegiatan {$act->activity_code} telah terlambat {$daysLate} hari dari tenggat ({$act->end_date->format('d/m/Y')}). Progres: {$act->progress_percentage}%.";

                $recipients = collect([$act->personInCharge])->merge($adminUsers)->merge($pimpinanUsers)->filter();
                foreach ($recipients as $u) {
                    $userKey = "{$key}_u{$u->id}";
                    if (! SystemAlert::where('unique_key', $userKey)->exists()) {
                        SystemAlert::create([
                            'user_id' => $u->id,
                            'alert_type' => 'activity_overdue',
                            'severity' => 'danger',
                            'subject_type' => Activity::class,
                            'subject_id' => $act->id,
                            'title' => 'Kegiatan Terlambat!',
                            'message' => $msg,
                            'action_url' => "/activities/{$act->id}",
                            'due_date' => $act->end_date,
                            'unique_key' => $userKey,
                        ]);
                        $createdCount++;
                    }
                }
            } elseif ($act->end_date <= $today->copy()->addDays($endDays) && $act->progress_percentage < 100) {
                // APPROACHING DEADLINE
                $daysLeft = (int) $today->diffInDays($act->end_date);
                $key = "act_near_end_{$act->id}_{$act->end_date->format('Ymd')}";
                $msg = "Kegiatan {$act->activity_code} berakhir dalam {$daysLeft} hari ({$act->end_date->format('d/m/Y')}) dan progres baru {$act->progress_percentage}%.";

                $recipients = collect([$act->personInCharge])->merge($adminUsers)->merge($pimpinanUsers)->filter();
                foreach ($recipients as $u) {
                    $userKey = "{$key}_u{$u->id}";
                    if (! SystemAlert::where('unique_key', $userKey)->exists()) {
                        SystemAlert::create([
                            'user_id' => $u->id,
                            'alert_type' => 'activity_deadline_approaching',
                            'severity' => 'warning',
                            'subject_type' => Activity::class,
                            'subject_id' => $act->id,
                            'title' => 'Kegiatan Mendekati Tenggat',
                            'message' => $msg,
                            'action_url' => "/activities/{$act->id}",
                            'due_date' => $act->end_date,
                            'unique_key' => $userKey,
                        ]);
                        $createdCount++;
                    }
                }
            }
        }

        // 3. Realisasi Status Revision
        $revRealizations = Realization::where('status', 'revision')->get();
        foreach ($revRealizations as $rel) {
            $key = "rel_rev_{$rel->id}_{$rel->updated_at->format('YmdHis')}";
            $user = $rel->activity ? $rel->activity->personInCharge : null;
            if ($user) {
                $userKey = "{$key}_u{$user->id}";
                if (! SystemAlert::where('unique_key', $userKey)->exists()) {
                    SystemAlert::create([
                        'user_id' => $user->id,
                        'alert_type' => 'realization_revision',
                        'severity' => 'warning',
                        'subject_type' => Realization::class,
                        'subject_id' => $rel->id,
                        'title' => 'Realisasi Transaksi Perlu Revisi',
                        'message' => "Realisasi (No Bukti: {$rel->receipt_number}) dikembalikan verifikator untuk direvisi: {$rel->verification_note}",
                        'action_url' => "/activities/{$rel->activity_id}",
                        'unique_key' => $userKey,
                    ]);
                    $createdCount++;
                }
            }
        }

        // 4. Resolve completed activity alerts
        $completedActIds = Activity::whereIn('status', ['completed', 'cancelled'])->pluck('id')->toArray();
        $unresolvedCompletedAlerts = SystemAlert::whereIn('alert_type', ['activity_starting_soon', 'activity_deadline_approaching', 'activity_overdue'])
            ->whereIn('subject_id', $completedActIds)
            ->whereNull('resolved_at')
            ->get();

        foreach ($unresolvedCompletedAlerts as $alert) {
            $alert->update(['resolved_at' => now()]);
            $resolvedCount++;
        }

        // Resolve realization revision alerts where status is no longer revision
        $resolvedRelAlerts = SystemAlert::where('alert_type', 'realization_revision')
            ->whereNull('resolved_at')
            ->get();

        foreach ($resolvedRelAlerts as $alert) {
            $rel = Realization::find($alert->subject_id);
            if (! $rel || $rel->status !== 'revision') {
                $alert->update(['resolved_at' => now()]);
                $resolvedCount++;
            }
        }

        return [
            'created' => $createdCount,
            'resolved' => $resolvedCount,
        ];
    }
}
