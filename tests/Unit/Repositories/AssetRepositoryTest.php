<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories;

use App\Models\Asset;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Mockery;
use Polis\Contracts\Services\Asset\AssetConfigurationServiceContract;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\AssetRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for AssetRepository — primarily the create() override for
 * uploaded-file handling and the NotImplemented findOrFail trait.
 *
 * The image-processing branch (storeFile + Imagick rotation/orientation)
 * lives behind PHP's Imagick extension and requires either Imagick to be
 * installed or a comprehensive Mockery setup of the global namespace —
 * skipped here; covered indirectly by the Consumer-Only integration suite.
 */
final class AssetRepositoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! class_exists(Asset::class, false)) {
            eval('namespace App\\Models; class Asset extends \\Polis\\Models\\BaseModelAbstract {}');
        }
    }

    private function buildRepository(?Asset $model = null, ?Filesystem $disk = null, string $serverUrl = 'https://cdn.test', string $baseDir = 'assets'): AssetRepository
    {
        $model = $model ?? new Asset;

        $disk = $disk ?? Mockery::mock(Filesystem::class);

        $factory = Mockery::mock(Factory::class);
        $factory->shouldReceive('disk')->with('public')->andReturn($disk);

        $config = Mockery::mock(AssetConfigurationServiceContract::class);
        $config->shouldReceive('getServerUrl')->andReturn($serverUrl);
        $config->shouldReceive('getBaseAssetDirectory')->andReturn($baseDir);

        return new AssetRepository(
            $model,
            $this->getGenericLogMock(),
            $factory,
            $config,
        );
    }

    public function test_find_or_fail_throws_not_implemented(): void
    {
        $repo = $this->buildRepository();

        $this->expectException(NotImplementedException::class);
        $repo->findOrFail(1);
    }

    public function test_create_with_uploaded_file_stores_publicly_and_sets_url(): void
    {
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('storePubliclyAs')->once()
            ->with('public/assets', 'name.jpg');
        $file->shouldReceive('getClientOriginalName')->andReturn('name.jpg');

        // Build the repository with a partial mock that overrides
        // parent::create so we don't have to drive the full Eloquent flow.
        $modelMock = Mockery::mock(Asset::class);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use ($modelMock) {
                $this->assertSame('https://cdn.test/assets/name.jpg', $data['url']);
                $this->assertArrayNotHasKey('uploaded_file', $data);

                return $modelMock;
            });
        $modelMock->wasRecentlyCreated = true;

        $repo = $this->buildRepository($modelMock);
        $repo->create(['uploaded_file' => $file]);
    }

    public function test_create_without_file_contents_or_uploaded_file_passes_through_unchanged(): void
    {
        $modelMock = Mockery::mock(Asset::class);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use ($modelMock) {
                $this->assertArrayNotHasKey('url', $data);
                $this->assertSame('label', $data['name']);

                return $modelMock;
            });
        $modelMock->wasRecentlyCreated = true;

        $repo = $this->buildRepository($modelMock);
        $repo->create(['name' => 'label']);
    }
}
