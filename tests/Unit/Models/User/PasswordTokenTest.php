<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use App\Models\User\PasswordToken;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polis\Tests\TestCase;

/**
 * Class PasswordTokenTest
 */
final class PasswordTokenTest extends TestCase
{
    public function test_user(): void
    {
        $model = new PasswordToken;

        $relation = $model->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);

        $this->assertEquals('password_tokens.user_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('users.id', $relation->getQualifiedOwnerKeyName());
    }
}
