<?php

declare(strict_types=1);

namespace Polis\Plugins;

/**
 * The Polis backend plugin contract.
 *
 * A plugin is a self-contained bundle of capabilities (bindings, listeners,
 * observers, routes, migrations, config, validators, policies, and — in a
 * future iteration — entity types) that a consumer application opts into by
 * listing its class string in `config('plugins.enabled')`.
 *
 * Plugins are discovered and applied by {@see PluginManager}, which is booted
 * by {@see PluginServiceProvider}. A consumer does NOT hand-edit any of the
 * Base* providers to activate a plugin — enabling the class in config is the
 * only wiring required.
 *
 * Implementations MUST be side-effect free in their constructor: the manager
 * instantiates every enabled plugin via the container and then calls
 * {@see PluginContract::register()} exactly once with a {@see PluginRegistrar}.
 * All capability declarations happen inside `register()` against the registrar
 * — never directly against the container — so the manager can aggregate,
 * de-duplicate, and apply them deterministically.
 *
 * This interface is intentionally mirrored on the frontend by polis-react's
 * plugin contract: `key`, `name`, `version`, and `capabilities` are the shared
 * identity/metadata surface across both halves of the Polis plugin system.
 */
interface PluginContract
{
    /**
     * Stable, machine-readable identifier for this plugin (e.g. "todo",
     * "time-tracking"). Used to namespace plugin config under
     * `config('plugin.<key>.*')` and to de-duplicate a plugin that is
     * enabled more than once. MUST be unique across enabled plugins and
     * MUST match the `key` the frontend plugin reports.
     */
    public function key(): string;

    /**
     * Human-readable display name (e.g. "Todo & Time Management").
     */
    public function name(): string;

    /**
     * Semantic version string of the plugin (e.g. "1.0.0"). Reported in the
     * aggregated plugin manifest so the frontend and tooling can assert
     * backend/frontend version parity.
     */
    public function version(): string;

    /**
     * The declared capability keys this plugin provides, for introspection
     * and for the frontend to gate UI on. These are advisory labels
     * (e.g. ['routes', 'migrations', 'listeners', 'entity:task']) describing
     * what the plugin contributes; the authoritative registration still
     * happens in {@see PluginContract::register()}.
     *
     * @return array<int, string>
     */
    public function capabilities(): array;

    /**
     * Declare every capability this plugin contributes by calling methods on
     * the supplied registrar. Called exactly once per boot by
     * {@see PluginManager}. MUST NOT touch the container, router, or event
     * dispatcher directly — route everything through the registrar so the
     * manager stays the single point of application.
     */
    public function register(PluginRegistrar $registrar): void;
}
