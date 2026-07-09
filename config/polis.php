<?php

use Polis\Models\User\TodoTaskNode;

return [

    'messaging_services' => [
        'slack_enabled' => env('POLIS_SLACK_ENABLED', false),
        'sms_enabled' => env('POLIS_SMS_ENABLED', false),
        'push_enabled' => env('POLIS_PUSH_ENABLED', false),
    ],

    'firebase' => [
        /*
         * Absolute path to the Firebase service-account JSON used by the
         * Firebase Admin SDK to authenticate FCM v1 requests. When null
         * the kreait/laravel-firebase package falls back to its own
         * default-discovery chain (GOOGLE_APPLICATION_CREDENTIALS,
         * gcloud ADC, etc.). Set FIREBASE_CREDENTIALS in .env to override.
         *
         * Required when polis.messaging_services.push_enabled=true unless
         * the host environment already provides credentials another way.
         */
        'credentials' => env('FIREBASE_CREDENTIALS'),
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

    'node_tree' => [
        /*
         * The model class that UserPageComponent's node-tree relations
         * (taskNodes / rootTaskNodes) resolve to. Defaults to the package's
         * Todo node model; a consumer that subclasses it can point these
         * relations at their own class here without editing the package.
         *
         * This replaces the previous hard `\App\Models\User\TodoTaskNode`
         * reference so polis-laravel stands alone and no longer reaches into a
         * consumer namespace.
         */
        'node_model' => TodoTaskNode::class,

        /*
         * The scope column on the node model that binds nodes to a component.
         */
        'component_foreign_key' => 'user_page_component_id',
    ],

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
