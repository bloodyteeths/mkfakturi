<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tax Period Locking
    |--------------------------------------------------------------------------
    |
    | Enable or disable tax period locking functionality.
    | When enabled, closed tax periods cannot be modified.
    |
    */

    'period_locking_enabled' => env('TAX_PERIOD_LOCKING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Period Close Buffer Days
    |--------------------------------------------------------------------------
    |
    | Number of days after a period end date before it can be closed.
    | This allows for late entries and reconciliation before locking.
    |
    */

    'period_close_buffer_days' => env('TAX_PERIOD_CLOSE_BUFFER_DAYS', 5),

];

// CLAUDE-CHECKPOINT
