<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Asset;

use App\Models\Asset;
use App\Models\User\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Polis\Contracts\Repositories\AssetRepositoryContract;
use Polis\Services\Asset\AssetImportService;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class AssetImportServiceTest extends TestCase
{
    /**
     * @var AssetRepositoryContract|(AssetRepositoryContract&MockInterface&LegacyMockInterface)|(AssetRepositoryContract&CustomMockInterface)|array|(MockInterface&LegacyMockInterface)|CustomMockInterface
     */
    private $assetRepository;

    /**
     * @var array|Client|(Client&MockInterface&LegacyMockInterface)|(Client&CustomMockInterface)|(MockInterface&LegacyMockInterface)|CustomMockInterface
     */
    private $client;

    private AssetImportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assetRepository = mock(AssetRepositoryContract::class);
        $this->client = mock(Client::class);

        $this->service = new AssetImportService($this->assetRepository, $this->client);
    }

    public function test_import_asset_fails_with_invalid_path()
    {
        $result = $this->service->importAsset(new User, 'hello my friend');

        $this->assertNull($result);
    }

    public function test_import_asset_fails_with_http_exception()
    {
        $this->client->shouldReceive('get')->andThrow(mock(ClientException::class));

        $result = $this->service->importAsset(new User, 'https://www.hello.bye/greetings.jpg');

        $this->assertNull($result);
    }

    public function test_import_asset_fails_with_invalid_status()
    {
        $response = mock(ResponseInterface::class);

        $this->client->shouldReceive('get')->andReturn($response);

        $response->shouldReceive('getStatusCode')->andReturn(404);

        $result = $this->service->importAsset(new User, 'https://www.hello.bye/greetings.jpg');

        $this->assertNull($result);
    }

    public function test_import_asset_success()
    {
        $asset = new Asset;

        $this->assetRepository->shouldReceive('create')->andReturn($asset);

        $response = mock(ResponseInterface::class);

        $this->client->shouldReceive('get')->andReturn($response);

        $response->shouldReceive('getStatusCode')->andReturn(200);

        $body = mock(StreamInterface::class);
        $response->shouldReceive('getBody')->andReturn($body);

        $body->shouldReceive('getContents')->andReturn('');

        $result = $this->service->importAsset(new User, 'https://www.hello.bye/greetings.jpg');

        $this->assertEquals($result, $asset);
    }
}
