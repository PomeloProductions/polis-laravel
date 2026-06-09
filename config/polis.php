<?php

return [

    'messaging_services' => [
        'slack_enabled' => env('POLIS_SLACK_ENABLED', false),
        'sms_enabled' => env('POLIS_SMS_ENABLED', false),
        'push_enabled' => env('POLIS_PUSH_ENABLED', false),
    ],

    'invitation_required' => env('INVITATION_REQUIRED', false),

    'model_cache' => [
        /*
         * Master switch for the HasModelCache trait. When false, models
         * using the trait skip observer registration entirely — useful
         * during heavy migrations or test setup where you don't want
         * cache invalidations firing on every saved row.
         */
        'enabled' => env('POLIS_MODEL_CACHE_ENABLED', true),
    ],
];
