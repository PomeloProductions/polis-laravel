<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\User;

use App\Models\User\ProfileImage;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Mockery;
use Polis\Contracts\Services\Asset\AssetConfigurationServiceContract;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\AssetRepository;
use Polis\Repositories\User\ProfileImageRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for ProfileImageRepository — a thin subclass of AssetRepository
 * that mixes in three NotImplemented traits (Delete, FindAll, Update).
 */
final class ProfileImageRepositoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Asset must be loaded first since ProfileImage extends it
        if (! class_exists(\App\Models\Asset::class, false)) {
            eval('namespace App\\Models; class Asset extends \\Polis\\Models\\BaseModelAbstract {}');
        }
        if (! class_exists(ProfileImage::class, false)) {
            eval('namespace App\\Models\\User; class ProfileImage extends \\App\\Models\\Asset {}');
        }
    }

    private function buildRepository(): ProfileImageRepository
    {
        $disk = Mockery::mock(Filesystem::class);
        $factory = Mockery::mock(Factory::class);
        $factory->shouldReceive('disk')->with('public')->andReturn($disk);

        $config = Mockery::mock(AssetConfigurationServiceContract::class);
        $config->shouldReceive('getServerUrl')->andReturn('https://cdn');
        $config->shouldReceive('getBaseAssetDirectory')->andReturn('profile');

        return new ProfileImageRepository(
            new ProfileImage,
            $this->getGenericLogMock(),
            $factory,
            $config,
        );
    }

    public function test_extends_asset_repository(): void
    {
        $this->assertInstanceOf(AssetRepository::class, $this->buildRepository());
    }

    public function test_delete_throws_not_implemented(): void
    {
        $repo = $this->buildRepository();
        $this->expectException(NotImplementedException::class);
        $repo->delete(Mockery::mock(\Polis\Models\BaseModelAbstract::class));
    }

    public function test_find_all_throws_not_implemented(): void
    {
        $repo = $this->buildRepository();
        $this->expectException(NotImplementedException::class);
        $repo->findAll();
    }

    public function test_update_throws_not_implemented(): void
    {
        $repo = $this->buildRepository();
        $this->expectException(NotImplementedException::class);
        $repo->update(Mockery::mock(\Polis\Models\BaseModelAbstract::class), []);
    }
}
