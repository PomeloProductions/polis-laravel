<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Polis\Services\ArchiveHelperService;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;
use ZipArchive;

class ArchiveHelperServiceTest extends TestCase
{
    /**
     * @var array|(MockInterface&LegacyMockInterface)|CustomMockInterface|ZipArchive|(ZipArchive&MockInterface&LegacyMockInterface)|(ZipArchive&CustomMockInterface)
     */
    private $zipArchive;

    /**
     * @var array|FilesystemAdapter|(FilesystemAdapter&MockInterface&LegacyMockInterface)|(FilesystemAdapter&CustomMockInterface)|(MockInterface&LegacyMockInterface)|CustomMockInterface
     */
    private $filesystem;

    private ArchiveHelperService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->zipArchive = mock(ZipArchive::class);
        $this->filesystem = mock(FilesystemAdapter::class);

        $this->service = new ArchiveHelperService($this->zipArchive);
    }

    public function test_unzip_archive_fails_without_archive()
    {
        $this->filesystem->shouldReceive('path')->with('test.zip')->andReturn('/tmp/test.zip');
        $this->zipArchive->shouldReceive('open')->andReturnFalse();

        $this->expectException(\RuntimeException::class);

        $this->service->unzipArchive($this->filesystem, 'test.zip');
    }

    public function test_unzip_archive_success()
    {
        $this->filesystem->shouldReceive('path')->with('test.zip')->andReturn('/tmp/test.zip');
        $this->zipArchive->shouldReceive('open')->andReturnTrue();

        $this->filesystem->shouldReceive('path')->with('test')->andReturn('/tmp/test');
        $this->zipArchive->shouldReceive('extractTo')->with('/tmp/test');

        $result = $this->service->unzipArchive($this->filesystem, 'test.zip');

        $this->assertEquals('test/', $result);
    }
}
