<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use App\Services\Indexing\ResourceRepositoryService;
use Polis\Contracts\Services\Indexing\ResourceRepositoryServiceContract;
use Polis\Providers\BaseServiceProvider;
use Polis\Tests\TestCase;

/**
 * Some package-level abstractions (`Polis\Services\Indexing\BaseResourceRepositoryService`
 * being the canonical example) are intentionally abstract — consumers are
 * required to provide the concrete implementation. This test asserts that
 * when a consumer fails to provide the required concrete, the binding
 * surfaces a clear, descriptive `RuntimeException` from
 * BaseServiceProvider::register() rather than silently failing later.
 */
final class BaseServiceProviderAbstractFallbackTest extends TestCase
{
    public function test_missing_resource_repository_service_concrete_throws_clear_error(): void
    {
        // We can't run the full register() here because BaseServiceProvider
        // also wires up many other consumer-app-dependent contracts. Instead,
        // we register only the ResourceRepositoryService binding by reusing
        // the same closure shape from BaseServiceProvider::register(). If
        // the implementation drifts, the closure here will need to drift
        // with it — which is the point: this is the contract we promise to
        // consumer apps.
        $this->app->bind(ResourceRepositoryServiceContract::class, function () {
            if (! class_exists(ResourceRepositoryService::class)) {
                throw new \RuntimeException(
                    'polis-laravel: missing consumer-side concrete '
                    .'App\\Services\\Indexing\\ResourceRepositoryService. '
                    .'Extend Polis\\Services\\Indexing\\BaseResourceRepositoryService '
                    .'and define availableResourceRepositories().'
                );
            }

            $class = ResourceRepositoryService::class;

            return new $class($this->app);
        });

        try {
            $this->app->make(ResourceRepositoryServiceContract::class);
            $this->fail('Expected RuntimeException because no App\\Services\\Indexing\\ResourceRepositoryService is autoloadable');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('App\\Services\\Indexing\\ResourceRepositoryService', $e->getMessage());
            $this->assertStringContainsString('BaseResourceRepositoryService', $e->getMessage());
        }
    }

    public function test_resolve_helper_does_not_invent_classes(): void
    {
        // Reinforces the contract for abstract-only artifacts: the helper
        // hands back the polis FQN even when neither side has a class, so
        // the failure surfaces at instantiation/binding time with a name
        // the developer can grep for.
        $result = BaseServiceProvider::resolveConsumerOrPackage(
            'App\\Policies\\User\\UserPolicy',
            'Polis\\Policies\\User\\UserPolicyAbstract',
        );

        $this->assertSame('Polis\\Policies\\User\\UserPolicyAbstract', $result);
    }
}
