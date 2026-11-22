<?php

use Illuminate\\Support\\Str;

return [
    'name' => env('HORIZON_NAME', env('APP_NAME', 'HelpDesk')),
    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'queue',
    'prefix' => env('HORIZON_PREFIX', Str::slug(env('APP_NAME', 'helpdesk'), '_').'_horizon:'),
    'middleware' => ['web'],
    'waits' => [
        'redis:notifications' => 60,
        'redis:default' => 60,
    ],
    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],
    'silenced' => [],
    'silenced_tags' => [],
    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],
    'fast_termination' => false,
    'memory_limit' => 128,
    'defaults' => [
        'supervisor-helpdesk' => [
            'connection' => 'redis',
            'queue' => ['notifications', 'default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses' => 1,
            'maxProcesses' => 3,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'maxTime' => 3600,
            'maxJobs' => 1000,
            'memory' => 128,
            'tries' => 5,
            'timeout' => 90,
            'nice' => 0,
        ],
    ],
    'environments' => [
        'production' => [
            'supervisor-helpdesk' => [
                'maxProcesses' => 8,
                'balanceMaxShift' => 2,
                'balanceCooldown' => 3,
            ],
        ],
        'local' => [
            'supervisor-helpdesk' => [
                'maxProcesses' => 3,
            ],
        ],
        'testing' => [
            'supervisor-helpdesk' => [
                'maxProcesses' => 1,
            ],
        ],
    ],
    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'routes',
        'composer.lock',
        'composer.json',
        '.env',
    ],
];
