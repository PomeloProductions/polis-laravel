<?php

declare(strict_types=1);

namespace Polis\Models\Traits;

use Polis\Contracts\Models\HasValidationRulesContract;

/**
 * Class HasValidationRules
 */
trait HasValidationRules
{
    /**
     * Get Validation Rules
     *
     * @param  array  $params  any additional parameters passed in
     */
    public function getValidationRules(?string $context = null, ...$params): array
    {
        $rules = [];
        $baseRules = $this->buildModelValidationRules(...$params);

        if (array_key_exists(HasValidationRulesContract::VALIDATION_RULES_BASE, $baseRules)) {

            $rules = $baseRules[HasValidationRulesContract::VALIDATION_RULES_BASE];

            foreach ($rules as $rule) {
                if (is_array($rule)) {
                    array_unshift($rule, 'bail');
                }
            }

            if (! is_null($context) && array_key_exists($context, $baseRules)) {
                foreach ($baseRules[$context] as $modifier => $keys) {
                    $modifierParts = explode('-', $modifier);
                    $position = $modifierParts[0];
                    $rule = $modifierParts[1];

                    foreach ($keys as $key) {
                        if (array_key_exists($key, $rules)) {
                            if ($position == 'prepend') {
                                array_unshift($rules[$key], $rule);
                            } else {
                                $rules[$key][] = $rule;
                            }
                        }
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Gets the validation rules on another model, and prepends all rules with whatever is passed in
     *
     * @param  mixed  ...$params
     */
    public function prependValidationRules(HasValidationRulesContract $relatedModel,
        string $prependKey,
        ...$params): array
    {
        $baseRules = $relatedModel->buildModelValidationRules(...$params);
        $prependedBaseRules = [];

        foreach ($baseRules as $groupKey => $rulesGroup) {
            $prependedRules = [];
            if ($groupKey == HasValidationRulesContract::VALIDATION_RULES_BASE) {
                foreach ($rulesGroup as $key => $rule) {
                    $prependedRules[$prependKey.$key] = $rule;
                }
            } else {
                foreach ($rulesGroup as $specialInstructionsKey => $fields) {
                    $prependedRules[$specialInstructionsKey] = array_map(fn (string $field) => $prependKey.$field, $fields);
                }
            }

            $prependedBaseRules[$groupKey] = $prependedRules;
        }

        return $prependedBaseRules;
    }
}
