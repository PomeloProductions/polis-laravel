<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories;

use App\Models\Asset;
use App\Models\User\User;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\AssetRepository;
use Polis\Services\Asset\AssetConfigurationService;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class AssetRepositoryTest
 */
final class AssetRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var AssetRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new AssetRepository(
            new Asset,
            $this->getGenericLogMock(),
            $this->app->make('filesystem'),
            new AssetConfigurationService(
                'http://localhost',
                '/storage',
            ),
        );
    }

    public function test_find_all_success(): void
    {
        Asset::factory()->count(5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function test_find_all_empty(): void
    {
        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function test_find_or_fail_success(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->findOrFail(54);
    }

    public function test_create_success(): void
    {
        $user = User::factory()->create();
        /** @var Asset $asset */
        $asset = $this->repository->create([
            'url' => 'a url',
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);

        $this->assertEquals('a url', $asset->url);
        $this->assertEquals($asset->owner_id, $user->id);
        $this->assertEquals($asset->owner_type, 'user');
    }

    public function test_update_success(): void
    {
        $asset = Asset::factory()->create();

        $this->repository->update($asset, [
            'url' => 'a new url',
        ]);

        $this->assertEquals('a new url', $asset->url);
    }

    public function test_delete_fails(): void
    {
        $asset = Asset::factory()->create();

        $this->repository->delete($asset);

        $this->assertNull(Asset::find($asset->id));
    }
}
