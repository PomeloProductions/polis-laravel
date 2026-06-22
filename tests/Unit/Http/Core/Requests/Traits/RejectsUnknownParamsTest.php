<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Requests\Traits;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Polis\Http\Core\Requests\Traits\RejectsUnknownParams;
use Polis\Tests\TestCase;

/**
 * Exercises the strict parameter enforcement trait that consuming
 * apps will adopt to reject 422 on any extraneous body key.
 */
final class RejectsUnknownParamsTest extends TestCase
{
    public function test_accepts_payload_with_only_declared_keys(): void
    {
        $request = $this->makeRequest(
            rules: ['name' => 'required|string', 'age' => 'integer'],
            payload: ['name' => 'Aiko', 'age' => 32],
        );

        $request->validateResolved();

        $this->assertSame(['name' => 'Aiko', 'age' => 32], $request->validated());
    }

    public function test_rejects_payload_with_a_single_extra_key(): void
    {
        $request = $this->makeRequest(
            rules: ['name' => 'required|string'],
            payload: ['name' => 'Aiko', 'rogue' => 'value'],
        );

        try {
            $request->validateResolved();
            $this->fail('Expected ValidationException for extra key, none thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('_extra', $e->errors());
            $this->assertSame(['Unknown field: rogue'], $e->errors()['_extra']);
            // Standard 422 contract — consumer apps depend on this.
            $this->assertSame(422, $e->status);
        }
    }

    public function test_rejects_payload_with_multiple_extra_keys(): void
    {
        $request = $this->makeRequest(
            rules: ['name' => 'required|string'],
            payload: ['name' => 'Aiko', 'rogue_a' => 1, 'rogue_b' => 2],
        );

        try {
            $request->validateResolved();
            $this->fail('Expected ValidationException for extra keys, none thrown.');
        } catch (ValidationException $e) {
            $extras = $e->errors()['_extra'] ?? [];
            $this->assertContains('Unknown field: rogue_a', $extras);
            $this->assertContains('Unknown field: rogue_b', $extras);
            $this->assertCount(2, $extras);
        }
    }

    public function test_does_not_flag_nested_keys_when_rule_uses_wildcard(): void
    {
        // `roles.*` declares the top-level `roles` field; nested entries
        // are validated by the wildcard rule and must not be reported as
        // extras.
        $request = $this->makeRequest(
            rules: [
                'roles' => 'array',
                'roles.*' => 'integer',
            ],
            payload: [
                'roles' => [1, 2, 3],
            ],
        );

        $request->validateResolved();

        $this->assertSame(['roles' => [1, 2, 3]], $request->validated());
    }

    public function test_still_reports_extras_when_standard_validation_fails(): void
    {
        // Both `_extra` (from this trait) and a missing-required error
        // should surface together — strict enforcement is additive.
        $request = $this->makeRequest(
            rules: ['name' => 'required|string'],
            payload: ['rogue' => 'value'],
        );

        try {
            $request->validateResolved();
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('name', $errors);
            $this->assertArrayHasKey('_extra', $errors);
            $this->assertSame(['Unknown field: rogue'], $errors['_extra']);
        }
    }

    public function test_empty_payload_against_empty_rules_passes(): void
    {
        $request = $this->makeRequest(
            rules: [],
            payload: [],
        );

        $request->validateResolved();

        $this->assertSame([], $request->validated());
    }

    /**
     * Build a FormRequest that adopts the trait under test and is fully
     * wired into the container — exactly the path Laravel takes when a
     * controller type-hints a FormRequest subclass.
     */
    private function makeRequest(array $rules, array $payload): FormRequest
    {
        $base = Request::create('/strict-params-test', 'POST', $payload);

        $form = new class($rules) extends FormRequest
        {
            use RejectsUnknownParams;

            /** @var array<string, mixed> */
            private array $declaredRules;

            public function __construct(array $declaredRules)
            {
                parent::__construct();
                $this->declaredRules = $declaredRules;
            }

            public function authorize(): bool
            {
                return true;
            }

            public function rules(): array
            {
                return $this->declaredRules;
            }
        };

        // Hydrate the FormRequest from the parent request and wire it to
        // the container so getValidatorInstance() can resolve the factory.
        $form->initialize(
            $base->query->all(),
            $base->request->all(),
            $base->attributes->all(),
            $base->cookies->all(),
            $base->files->all(),
            $base->server->all(),
            $base->getContent(),
        );
        $form->setContainer($this->app);
        $form->setRedirector($this->app->make('redirect'));

        return $form;
    }
}
