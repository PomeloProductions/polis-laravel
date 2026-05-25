<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use App\Models\User\ProfileImage;
use Polis\Tests\TestCase;

/**
 * Class ProfileImageTest
 */
final class ProfileImageTest extends TestCase
{
    public function test_organization(): void
    {
        $model = new ProfileImage;
        $relation = $model->organization();

        $this->assertEquals('assets.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('organizations.profile_image_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_user(): void
    {
        $model = new ProfileImage;
        $relation = $model->user();

        $this->assertEquals('assets.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('users.profile_image_id', $relation->getQualifiedForeignKeyName());
    }
}
