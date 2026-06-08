<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Traits;

/**
 * Shared trait for fixture models that need to extend
 * Polis\Models\BaseModelAbstract (so they satisfy repository methods
 * typed against BaseModelAbstract) while still supporting the legacy
 * `Mockery::mock(Model::class); $mock->id = 5;` policy/validator test
 * pattern.
 *
 * Eloquent's default __set routes property assignment through
 * setAttribute(), which on a Mockery type-mock throws "no expectations
 * were specified". By bypassing the attribute-mutator chain for direct
 * `$mock->prop = value` writes (storing in the Eloquent $attributes
 * array directly), the same fixture supports:
 *
 *   - legacy `$mock->id = 5` policy tests, and
 *   - repository tests' `$mock->shouldReceive('setAttribute')` pattern
 *     (setAttribute is still a real method on BaseModelAbstract, so
 *     Mockery's strict allowMockingNonExistentMethods accepts it).
 *
 * Real Eloquent flows (forceFill / fill / update / save) still route
 * through setAttribute, which is what repository tests stub.
 */
trait MockeryFriendlyAttributesTrait
{
    /**
     * On Mockery mocks of this class:
     *
     *   - If the test has stubbed `setAttribute`, route through it so the
     *     Mockery expectation is satisfied (this is what
     *     BaseRepositoryAbstract's `$newModel->{$key} = $value` forced-
     *     values path relies on).
     *   - Otherwise, bypass Eloquent's attribute-mutator chain and store
     *     the assignment in the attributes array directly. This is what
     *     legacy policy/validator tests' `$mock->id = 5` patterns expect.
     *
     * On real instances, defer to Eloquent's standard __set so behaviour
     * matches production.
     */
    public function __set($key, $value)
    {
        if ($this instanceof \Mockery\LegacyMockInterface) {
            if (method_exists($this, 'mockery_getExpectationsFor')
                && $this->mockery_getExpectationsFor('setAttribute') !== null) {
                $this->setAttribute($key, $value);

                return;
            }

            $this->attributes[$key] = $value;

            return;
        }

        parent::__set($key, $value);
    }

    public function __get($key)
    {
        if ($this instanceof \Mockery\LegacyMockInterface) {
            if (array_key_exists($key, $this->attributes ?? [])) {
                return $this->attributes[$key];
            }

            // If the test stubbed getAttribute, route through it.
            if (method_exists($this, 'mockery_getExpectationsFor')
                && $this->mockery_getExpectationsFor('getAttribute') !== null) {
                return $this->getAttribute($key);
            }

            return null;
        }

        return parent::__get($key);
    }

    /**
     * Mirror __get's bypass for the isset()/null-coalesce path so
     * `$subscriber->first_name ?? ''` on a Mockery mock doesn't escalate
     * to Eloquent's offsetExists() (which requires a separate Mockery
     * expectation).
     */
    public function __isset($key)
    {
        if ($this instanceof \Mockery\LegacyMockInterface) {
            return isset($this->attributes[$key]);
        }

        return parent::__isset($key);
    }
}
