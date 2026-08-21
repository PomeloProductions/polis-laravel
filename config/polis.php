<?php

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

    'invitations' => [
        /*
         * Base URL of the consumer application's "accept invitation" page.
         * The invite email links here with the invitation token appended so
         * the invitee can set a password and activate their account. This is
         * deliberately app-agnostic — every consumer points it at their own
         * frontend/domain via INVITATION_ACCEPT_URL_BASE and NEVER relies on
         * a hardcoded domain baked into the package.
         *
         * Example: https://app.example.com/accept-invitation
         *
         * When null, InvitationUrlService falls back to APP_URL + the path
         * below so a working (if generic) link still ships.
         */
        'accept_url_base' => env('INVITATION_ACCEPT_URL_BASE'),

        /*
         * Path appended to APP_URL when accept_url_base is not set. Only used
         * as the fallback described above.
         */
        'accept_url_fallback_path' => env('INVITATION_ACCEPT_URL_PATH', '/accept-invitation'),

        /*
         * Query-string parameter name the token is passed under on the accept
         * URL (e.g. .../accept-invitation?invitation_token=abc123). Consumer
         * frontends read this param, then POST it back to /auth/sign-up as
         * `invitation_token` alongside the chosen password.
         */
        'accept_url_token_param' => env('INVITATION_ACCEPT_URL_TOKEN_PARAM', 'invitation_token'),
    ],

    'node_tree' => [
        /*
         * The model class that UserPageComponent's node-tree relations
         * (taskNodes / rootTaskNodes) resolve to. The package ships no default
         * node model, so a consumer that uses the node-tree relations must set
         * this to their own model class (any model using the HasNodeTree trait).
         */
        'node_model' => null,

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
