<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Traits;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Polis\Http\Core\Requests\BaseRequestAbstract;

/**
 * Trait RejectsUnknownParams
 *
 * Foundation for strict parameter enforcement on Polis-backed FormRequests.
 *
 * Drop this trait into any FormRequest (typically a subclass of
 * {@see BaseRequestAbstract}) to make the request
 * fail with a 422 ValidationException when the incoming payload contains
 * any top-level key that is not declared in the request's {@see rules()}.
 *
 * The strict check runs as a validator "after" callback so it participates
 * in the framework's normal failedValidation() flow — no controller code
 * changes required. The reported error lives on the synthetic `_extra`
 * key and lists each unexpected field by name.
 *
 * Composability
 * -------------
 * The trait exposes its own {@see withValidator()} method. If a consuming
 * FormRequest already defines `withValidator`, alias the trait method with
 * `use RejectsUnknownParams { withValidator as rejectsUnknownParamsWithValidator; }`
 * and call `$this->rejectsUnknownParamsWithValidator($validator);` from the
 * concrete `withValidator()`. Alternatively, call the underlying check
 * directly via {@see assertNoUnknownParams()}.
 *
 * Nested keys
 * -----------
 * Only top-level input keys are compared against top-level rule keys.
 * A rule like `roles.*` declares the top-level field `roles`; nested
 * elements under `roles` are intentionally not flagged as "extra".
 */
trait RejectsUnknownParams
{
    /**
     * Register the strict-params after-callback on the request's validator.
     *
     * Laravel calls this hook (if defined) during getValidatorInstance().
     * We attach an {@see Validator::after()} callback that checks for any
     * top-level input key that is not declared in {@see rules()}.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->assertNoUnknownParams($validator);
        });
    }

    /**
     * Inspect the request's input vs. the validator's resolved rules and
     * add a `_extra` error per unknown top-level key.
     *
     * The validator's rules are dot-keyed (e.g. `roles.*`); we collapse
     * those to their root (`roles`) before comparing.
     */
    protected function assertNoUnknownParams(Validator $validator): void
    {
        $allowed = $this->allowedTopLevelKeys($validator);
        $incoming = array_keys($this->all());

        foreach ($incoming as $key) {
            if (! in_array($key, $allowed, true)) {
                $validator->errors()->add('_extra', 'Unknown field: '.$key);
            }
        }
    }

    /**
     * Collapse the validator's (potentially dot-keyed) rule set down to its
     * top-level field names. We pull from the validator so that rules
     * injected via the container (e.g. `rules(Model $m)`) are honoured.
     *
     * @return array<int, string>
     */
    protected function allowedTopLevelKeys(Validator $validator): array
    {
        $ruleKeys = array_keys($validator->getRules());

        $topLevel = array_map(
            static fn (string $ruleKey): string => Arr::first(explode('.', $ruleKey)) ?? $ruleKey,
            $ruleKeys,
        );

        return array_values(array_unique($topLevel));
    }
}
