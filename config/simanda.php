<?php

return [
    'alerts' => [
        'activity_start_days' => (int) env('SIMANDA_ALERT_START_DAYS', 7),
        'activity_end_days' => (int) env('SIMANDA_ALERT_END_DAYS', 7),
        'verification_waiting_days' => (int) env('SIMANDA_ALERT_VERIFICATION_DAYS', 3),
        'revision_waiting_days' => (int) env('SIMANDA_ALERT_REVISION_DAYS', 3),
        'draft_realization_days' => (int) env('SIMANDA_ALERT_DRAFT_DAYS', 3),
    ],

    'backup' => [
        'path' => env('SIMANDA_BACKUP_PATH', storage_path('app/private/backups')),
        'daily_keep' => (int) env('SIMANDA_BACKUP_DAILY_KEEP', 7),
        'weekly_keep' => (int) env('SIMANDA_BACKUP_WEEKLY_KEEP', 4),
        'monthly_keep' => (int) env('SIMANDA_BACKUP_MONTHLY_KEEP', 6),
    ],

    'disk' => [
        'warning_percent' => (int) env('SIMANDA_DISK_WARNING_PERCENT', 15),
    ],
];
