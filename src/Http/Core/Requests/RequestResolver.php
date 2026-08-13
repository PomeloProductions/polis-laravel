<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests;

use Illuminate\Contracts\Container\Container;

/**
 * Resolves consumer-app FormRequest overrides for the package's own request
 * classes.
 *
 * Background
 * ----------
 * The abstract controllers in src/Http/Core/Controllers/* method-inject
 * request objects. Historically they type-hinted `App\Http\Core\Requests\...`
 * classes, which forced every consumer to ship an empty
 * `App\Http\Core\Requests\...\XRequest extends Polis\...\XRequest {}` shim just
 * so PHP reflection (used by Laravel's route dependency resolver) could find
 * the class.
 *
 * The controllers now type-hint the package's own concrete
 * `Polis\Http\Core\Requests\...\XRequest`. That alone lets a consumer drop the
 * shim: Laravel instantiates the package request directly. When a consumer DOES
 * want to override a request (custom rules / authorization) it still ships an
 * `App\Http\Core\Requests\...\XRequest` — and {@see registerBindings()} rebinds
 * the package request FQN to that override in the container so the override is
 * what actually gets built and injected. The override must extend the package
 * request (as the shims already do), so it satisfies the controller type-hint.
 *
 * This keeps the behaviour identical for existing consumers (their App request
 * still wins) while letting new/cleaned-up consumers omit the shim entirely.
 */
final class RequestResolver
{
    /**
     * Map a package request FQN to the equivalent consumer-app FQN.
     *
     * `Polis\Http\Core\Requests\Feature\IndexRequest`
     *   -> `App\Http\Core\Requests\Feature\IndexRequest`
     *
     * Returns null when the class is not a package request under the
     * `Polis\Http\Core\Requests\` root, so unrelated classes are never
     * rewritten.
     */
    public static function appOverrideFor(string $polisRequest): ?string
    {
        $prefix = 'Polis\\Http\\Core\\Requests\\';

        if (! str_starts_with($polisRequest, $prefix)) {
            return null;
        }

        return 'App\\Http\\Core\\Requests\\'.substr($polisRequest, strlen($prefix));
    }

    /**
     * Resolve the class that should actually be instantiated for a given
     * package request: the consumer override when it exists, otherwise the
     * package request itself.
     *
     * @param  class-string  $polisRequest
     * @return class-string
     */
    public static function resolve(string $polisRequest): string
    {
        $appOverride = self::appOverrideFor($polisRequest);

        return ($appOverride !== null && class_exists($appOverride))
            ? $appOverride
            : $polisRequest;
    }

    /**
     * Discover the concrete package request FQNs shipped under
     * src/Http/Core/Requests. Abstract classes (e.g. the `...RequestAbstract`
     * bases and the shared `BaseRequest*` classes) are skipped because they are
     * never method-injected directly.
     *
     * @return list<class-string>
     */
    public static function packageRequests(): array
    {
        // This file lives at src/Http/Core/Requests/RequestResolver.php, so
        // __DIR__ is already the package requests root.
        $baseDir = __DIR__;

        if (! is_dir($baseDir)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS)
        );

        $requests = [];

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            // Only non-abstract classes named `...Request` are injectable.
            if (! preg_match('/^\s*(?:final\s+)?class\s+(\w+Request)\b/m', $contents, $m)) {
                continue;
            }

            if (! preg_match('/^namespace\s+([^;]+);/m', $contents, $nm)) {
                continue;
            }

            $requests[] = $nm[1].'\\'.$m[1];
        }

        return $requests;
    }

    /**
     * Register container bindings so that any package request with a
     * consumer-app override resolves to that override.
     *
     * Only requests that actually have an `App\` override are bound; requests
     * without an override are left unbound and Laravel instantiates the
     * package concrete directly (no shim required). This makes the method a
     * no-op for a consumer that ships zero overrides.
     *
     * @param  Container  $container
     * @param  iterable<class-string>  $packageRequests  Package request FQNs to consider.
     */
    public static function registerBindings($container, iterable $packageRequests): void
    {
        foreach ($packageRequests as $polisRequest) {
            $appOverride = self::appOverrideFor($polisRequest);

            if ($appOverride === null || ! class_exists($appOverride)) {
                continue;
            }

            // Bind the package FQN to the consumer override. Laravel's route
            // dependency resolver type-hints the package FQN; this makes the
            // container build the override instead.
            $container->bind($polisRequest, static fn ($app) => $app->make($appOverride));
        }
    }
}
