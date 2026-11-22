<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Service-level agreement targets (calendar minutes)
    |--------------------------------------------------------------------------
    |
    | These defaults are intentionally centralized so deployments can tune
    | response and resolution targets without changing application code.
    |
    */
    'sla' => [
        'warning_minutes' => (int) env('HELPDESK_SLA_WARNING_MINUTES', 60),
        'priorities' => [
            'urgent' => ['first_response' => 15, 'resolution' => 240],
            'high' => ['first_response' => 60, 'resolution' => 480],
            'normal' => ['first_response' => 240, 'resolution' => 1440],
            'low' => ['first_response' => 480, 'resolution' => 2880],
        ],
        'active_statuses' => ['open', 'in_progress', 'waiting_for_customer'],
        'resolved_statuses' => ['resolved', 'closed'],
    ],
];
