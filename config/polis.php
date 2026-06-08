<?php

return [

    'messaging_services' => [
        'slack_enabled' => env('POLIS_SLACK_ENABLED', false),
        'sms_enabled' => env('POLIS_SMS_ENABLED', false),
        'push_enabled' => env('POLIS_PUSH_ENABLED', false),
    ],

    'slack' => [
        /*
         * Username that the Slack notification client uses when posting
         * messages. Defaults to APP_NAME so consumer apps appear under
         * their own brand without explicit configuration. Override via
         * POLIS_SLACK_USERNAME if the bot identity needs to be distinct
         * from the application name.
         */
        'username' => env('POLIS_SLACK_USERNAME', env('APP_NAME', 'Polis')),
    ],

    'invitation_required' => env('INVITATION_REQUIRED', false),
];
