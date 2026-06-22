<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Requests\Traits;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Polis\Http\Core\Requests\Traits\RejectsUnknownParams;
use Polis\Tests\TestCase;

/**
 * End-to-end coverage that proves the {@see RejectsUnknownParams} trait
 * integrates cleanly with a Laravel route → FormRequest → controller
 * pipeline, including the framework's standard ValidationException
 * → 422 JSON response transformation.
 *
 * Consumer apps wiring this trait into their own FormRequests can use
 * this test as a reference for the contract they will inherit.
 */
final class RejectsUnknownParamsFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post(
            '/__test/strict-params',
            fn (RejectsUnknownParamsFeatureTestRequest $request) => new JsonResponse([
                'received' => $request->validated(),
            ]),
        );
    }

    public function test_happy_path_returns_200_with_validated_payload(): void
    {
        $response = $this->postJson('/__test/strict-params', [
            'title' => 'A Polis Topic',
            'priority' => 3,
        ]);

        $response->assertOk();
        $response->assertExactJson([
            'received' => [
                'title' => 'A Polis Topic',
                'priority' => 3,
            ],
        ]);
    }

    public function test_missing_required_field_returns_422(): void
    {
        $response = $this->postJson('/__test/strict-params', [
            'priority' => 3,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_wrong_type_returns_422(): void
    {
        $response = $this->postJson('/__test/strict-params', [
            'title' => 'A Polis Topic',
            'priority' => 'not-an-integer',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['priority']);
    }

    public function test_extra_param_is_rejected_via_the_synthetic_extra_key(): void
    {
        $response = $this->postJson('/__test/strict-params', [
            'title' => 'A Polis Topic',
            'priority' => 3,
            'attacker_field' => 'sneaky',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['_extra']);
        $response->assertJsonFragment([
            '_extra' => ['Unknown field: attacker_field'],
        ]);
    }

    public function test_multiple_extra_params_are_all_reported(): void
    {
        $response = $this->postJson('/__test/strict-params', [
            'title' => 'A Polis Topic',
            'priority' => 3,
            'rogue_a' => 1,
            'rogue_b' => 2,
        ]);

        $response->assertStatus(422);
        $errors = $response->json('errors._extra');
        $this->assertContains('Unknown field: rogue_a', $errors);
        $this->assertContains('Unknown field: rogue_b', $errors);
    }
}

/**
 * Concrete FormRequest used by the test route.
 *
 * Defined at file-scope (not anonymous) because Laravel's container
 * needs a resolvable class name to instantiate the request via type-hint
 * in the route closure.
 */
final class RejectsUnknownParamsFeatureTestRequest extends FormRequest
{
    use RejectsUnknownParams;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:120',
            'priority' => 'integer|min:1|max:5',
        ];
    }
}
