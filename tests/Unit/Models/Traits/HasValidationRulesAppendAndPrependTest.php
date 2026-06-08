<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Traits;

use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\Traits\HasValidationRules;
use Polis\Tests\TestCase;

/**
 * Exercises the append-path (non-prepend) of HasValidationRules::getValidationRules
 * and the prependValidationRules() helper, which were not exercised by the
 * original HasValidationRulesTest's prepend-only coverage.
 */
final class HasValidationRulesAppendAndPrependTest extends TestCase
{
    public function test_context_appends_when_modifier_is_not_prepend(): void
    {
        $model = new class implements HasValidationRulesContract
        {
            use HasValidationRules;

            public function buildModelValidationRules(...$params): array
            {
                return [
                    HasValidationRulesContract::VALIDATION_RULES_BASE => ['name' => ['string']],
                    'create' => ['append-required' => ['name']],
                ];
            }
        };

        // Position 'append' falls through to the else branch which appends.
        $this->assertSame(
            ['name' => ['string', 'required']],
            $model->getValidationRules('create'),
        );
    }

    public function test_prepend_validation_rules_prepends_key_to_each_rule(): void
    {
        $related = new class implements HasValidationRulesContract
        {
            use HasValidationRules;

            public function buildModelValidationRules(...$params): array
            {
                return [
                    HasValidationRulesContract::VALIDATION_RULES_BASE => [
                        'name' => ['string'],
                        'email' => ['email'],
                    ],
                    'create' => ['prepend-required' => ['name', 'email']],
                ];
            }
        };

        $consumer = new class implements HasValidationRulesContract
        {
            use HasValidationRules;

            public function buildModelValidationRules(...$params): array
            {
                return [];
            }
        };

        $prefixed = $consumer->prependValidationRules($related, 'user.');

        // Base rules keys should be prefixed.
        $this->assertArrayHasKey(HasValidationRulesContract::VALIDATION_RULES_BASE, $prefixed);
        $this->assertSame(
            ['user.name' => ['string'], 'user.email' => ['email']],
            $prefixed[HasValidationRulesContract::VALIDATION_RULES_BASE],
        );

        // Special-instruction groups (non-base) should also have their
        // field references prefixed.
        $this->assertArrayHasKey('create', $prefixed);
        $this->assertSame(
            ['prepend-required' => ['user.name', 'user.email']],
            $prefixed['create'],
        );
    }

    public function test_prepend_validation_rules_passes_params_through(): void
    {
        $related = new class implements HasValidationRulesContract
        {
            use HasValidationRules;

            public function buildModelValidationRules(...$params): array
            {
                // Params are forwarded as-is — record them to verify.
                return [
                    HasValidationRulesContract::VALIDATION_RULES_BASE => [
                        'name' => $params,
                    ],
                ];
            }
        };

        $consumer = new class implements HasValidationRulesContract
        {
            use HasValidationRules;

            public function buildModelValidationRules(...$params): array
            {
                return [];
            }
        };

        $prefixed = $consumer->prependValidationRules($related, 'foo.', 'extra', 42);

        $this->assertSame(
            ['extra', 42],
            $prefixed[HasValidationRulesContract::VALIDATION_RULES_BASE]['foo.name'],
        );
    }
}
