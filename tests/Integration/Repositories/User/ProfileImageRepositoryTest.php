<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\User;

use App\Models\Asset;
use App\Models\User\ProfileImage;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\User\ProfileImageRepository;
use Polis\Services\Asset\AssetConfigurationService;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ProfileImageRepositoryTest
 */
final class ProfileImageRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var ProfileImageRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new ProfileImageRepository(
            new ProfileImage,
            $this->getGenericLogMock(),
            $this->app->make('filesystem'),
            new AssetConfigurationService(
                'http://localhost',
                '/storage',
            ),
        );
    }

    public function test_find_all_fails(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->findAll();
    }

    public function test_find_or_fail_success(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->findOrFail(54);
    }

    public function test_create_success(): void
    {
        /** @var Asset $asset */
        $asset = $this->repository->create([
            'url' => 'a url',
        ]);

        $this->assertEquals('a url', $asset->url);
    }

    public function test_update_fails(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->update(new Asset, []);
    }

    public function test_delete_fails(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->delete(new Asset);
    }
}
