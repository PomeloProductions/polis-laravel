<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Enabled Polis plugins
     |--------------------------------------------------------------------------
     |
     | A list of fully-qualified class strings, each implementing
     | Polis\Plugins\PluginContract. The PluginManager (booted by
     | Polis\Plugins\PluginServiceProvider) instantiates each enabled plugin,
     | calls register() to collect its declared capabilities (bindings,
     | listeners, observers, routes, migrations, config, validators, policies),
     | and applies them — no hand-editing of any provider required.
     |
     | Default is an empty array: a consumer with no plugins (or no published
     | config/plugins.php at all) gets exactly the same behaviour as before the
     | plugin system existed.
     |
     | Example:
     | 'enabled' => [
     |     \App\Plugins\TodoPlugin::class,
     | ],
     */
    'enabled' => [],

];
