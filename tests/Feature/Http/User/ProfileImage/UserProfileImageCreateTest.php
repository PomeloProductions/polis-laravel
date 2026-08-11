<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\User\ProfileImage;

use App\Models\User\ProfileImage;
use App\Models\User\User;
use Illuminate\Support\Facades\Storage;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserProfileImageCreateTest
 */
final class UserProfileImageCreateTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/users/';

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();

        $this->user = User::factory()->create();
        $this->path .= $this->user->id.'/profile-images';

        Storage::fake('public');
    }

    public function test_not_logged_user_blocked(): void
    {
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_create_successful(): void
    {
        $this->actingAs($this->user);

        $properties = [
            'file_contents' => 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAgY0hSTQAAeiYAAICEAAD6AAAAgOgAAHUwAADqYAAAOpgAABdwnLpRPAAAAPBQTFRF////+/v77/Dx7e7vw8bL6evs/v7+zdDVd32Rd3qWdXiUZGiGf4Kdycfb4N7sxcnOaGeUQzmNQjiLQzmMQjiMQTiLeHGsxsrOZWSTOjCIOS+HOC2FcGmo/f39+/v8xMjMOzGIOjCHOC6GcWqoys3SanOBWGJyWGNyWWNzanOAZ2WUSFRlCBgwCRkxCBkwPkpbaGaVVWBvCRkw2tzfhIuXcHmHcXqHcnuId4CLZmSTNy2FxcnNZ2aTRDqMQzqLRDuMRz6PfHWv0tXZgIaXgIObfoGZaW6Iio2k1dTj5+bx/f3++Pj5+Pj49fb2ztHU8fLz/mE5kAAAAAFiS0dEAIgFHUgAAAAJcEhZcwAALiMAAC4jAXilP3YAAACLSURBVBjTY2DABRiZmJhZWNkQAuwcnFzcPLx8cAF+AUEhYRFRMbiAuISklJSUtAwDg6wcCLDJSygoKioqKTOoqKqpq6uraWhqQQW0dXRBQE/fACpgqGuEKmBsYmoGBOYWMC1wWyzRBcDWWsnABaxtbO3s7R0c4QJOzi6ubu4ennABL28fXz9/LL4GAJ3CFbkYlIJgAAAAJXRFWHRkYXRlOmNyZWF0ZQAyMDE4LTEyLTE3VDAwOjUwOjE1KzAwOjAwR3P0NQAAACV0RVh0ZGF0ZTptb2RpZnkAMjAxOC0xMi0xN1QwMDo1MDoxNSswMDowMDYuTIkAAAARdEVYdGpwZWc6Y29sb3JzcGFjZQAyLHVVnwAAACB0RVh0anBlZzpzYW1wbGluZy1mYWN0b3IAMXgxLDF4MSwxeDHplfxwAAAACnRFWHRyZGY6QWx0ACAgzYPMqwAAAEZ0RVh0c29mdHdhcmUASW1hZ2VNYWdpY2sgNi43LjgtOSAyMDE0LTA1LTEyIFExNiBodHRwOi8vd3d3LmltYWdlbWFnaWNrLm9yZ9yG7QAAAAAYdEVYdFRodW1iOjpEb2N1bWVudDo6UGFnZXMAMaf/uy8AAAAYdEVYdFRodW1iOjpJbWFnZTo6aGVpZ2h0ADE5Mg8AcoUAAAAXdEVYdFRodW1iOjpJbWFnZTo6V2lkdGgAMTky06whCAAAABl0RVh0VGh1bWI6Ok1pbWV0eXBlAGltYWdlL3BuZz+yVk4AAAAXdEVYdFRodW1iOjpNVGltZQAxNTQ1MDA3ODE1F0XONAAAAA90RVh0VGh1bWI6OlNpemUAMEJClKI+7AAAAFZ0RVh0VGh1bWI6OlVSSQBmaWxlOi8vL21udGxvZy9mYXZpY29ucy8yMDE4LTEyLTE3LzdkNzRlMWFiNDYzMGU0MDYwNjEyODlkZjkwZGNmYTY5Lmljby5wbmdF4iPwAAAAEnRFWHR4bXBNTTpEZXJpdmVkRnJvbQCXqCQIAAAAAElFTkSuQmCC',
        ];

        $response = $this->json('POST', $this->path, $properties);

        /** @var ProfileImage $profileImage */
        $response->assertStatus(201);
        $profileImage = ProfileImage::find($response->json()['id']);
        Storage::disk('public')->assertExists(str_replace(config('app.asset_url'), '', $profileImage->url));

        /** @var User $updatedUser */
        $updatedUser = User::find($this->user->id);
        $this->assertEquals($profileImage->id, $updatedUser->profile_image_id);
    }

    public function test_create_fails_required_fields_missing(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'file_contents' => ['The file contents field is required.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_string_field(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path, [
            'file_contents' => 324,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'file_contents' => ['The file contents must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_file_type(): void
    {
        $this->actingAs($this->user);

        $properties = [
            'file_contents' => 'dGV4dA==',
        ];

        $response = $this->json('POST', $this->path, $properties);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'mime_type' => ['The selected mime type is invalid.'],
            ],
        ]);
    }
}
