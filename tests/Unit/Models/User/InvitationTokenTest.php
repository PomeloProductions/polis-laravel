<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use App\Models\User\InvitationToken;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polis\Tests\TestCase;

/**
 * Class InvitationTokenTest
 */
final class InvitationTokenTest extends TestCase
{
    public function test_role(): void
    {
        $model = new InvitationToken;

        $relation = $model->role();

        $this->assertInstanceOf(BelongsTo::class, $relation);

        $this->assertEquals('invitation_tokens.role_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('roles.id', $relation->getQualifiedOwnerKeyName());
    }

    public function test_is_used_returns_true_when_used_at_is_set(): void
    {
        $model = new InvitationToken;
        $model->used_at = now();

        $this->assertTrue($model->isUsed());
    }

    public function test_is_used_returns_false_when_used_at_is_null(): void
    {
        $model = new InvitationToken;
        $model->used_at = null;

        $this->assertFalse($model->isUsed());
    }
}
