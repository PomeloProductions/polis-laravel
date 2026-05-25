<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Polis\Services\DirectoryCopyService;
use Polis\Tests\TestCase;

final class DirectoryCopyServiceTest extends TestCase
{
    public function test_copy_directory_with_only_files(): void
    {
        $service = new DirectoryCopyService;
        $from = mock(FilesystemAdapter::class);
        $to = mock(FilesystemAdapter::class);

        $fromPath = '/home/user1/';
        $toPath = '/home/user2/';

        $from->shouldReceive('files')->with($fromPath)->andReturn([
            $fromPath.'test.txt',
            $fromPath.'hi.jpg',
        ]);
        $from->shouldReceive('directories')->with($fromPath)->andReturn([]);

        $from->shouldReceive('get')->times(2)->andReturn('');

        $to->shouldReceive('put')->with($toPath.'test.txt', '');
        $to->shouldReceive('put')->with($toPath.'hi.jpg', '');

        $service->copyDirectory($from, $to, $fromPath, $toPath);
    }

    public function test_copy_directory_recursively(): void
    {
        $service = new DirectoryCopyService;
        $from = mock(FilesystemAdapter::class);
        $to = mock(FilesystemAdapter::class);

        $fromPath = '/home/user1/';
        $toPath = '/home/user2/';

        $from->shouldReceive('files')->with($fromPath)->andReturn([
            $fromPath.'test.txt',
            $fromPath.'hi.jpg',
        ]);
        $from->shouldReceive('directories')->with($fromPath)->andReturn([
            $fromPath.'temp/',
        ]);

        $from->shouldReceive('get')->times(3)->andReturn('');

        $to->shouldReceive('put')->with($toPath.'test.txt', '');
        $to->shouldReceive('put')->with($toPath.'hi.jpg', '');

        $to->shouldReceive('createDir')->with($toPath.'temp');

        $from->shouldReceive('files')->with($fromPath.'temp/')->andReturn([
            $fromPath.'temp/download.zip',
        ]);
        $from->shouldReceive('directories')->with($fromPath.'temp/')->andReturn([]);

        $to->shouldReceive('put')->with($toPath.'temp/download.zip', '');

        $service->copyDirectory($from, $to, $fromPath, $toPath);

    }
}
