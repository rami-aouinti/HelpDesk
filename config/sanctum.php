<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sanctum token last-used tracking
    |--------------------------------------------------------------------------
    |
    | Updating last_used_at on every bearer-token request adds a PostgreSQL
    | write to otherwise read-only API traffic. Token validity is based on
    | token creation/expiry, not this activity timestamp. Enable this only
    | when the application explicitly needs per-request activity tracking.
    |
    */

    'last_used_at' => env('SANCTUM_TRACK_LAST_USED_AT', false),

];
