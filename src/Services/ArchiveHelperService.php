<?php

declare(strict_types=1);

namespace Polis\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Polis\Contracts\Services\ArchiveHelperServiceContract;
use ZipArchive;

class ArchiveHelperService implements ArchiveHelperServiceContract
{
    public function __construct(private ZipArchive $zipArchive) {}

    /**
     * Unzips the archive, and returns the path to the archive
     */
    public function unzipArchive(FilesystemAdapter $filesystem, string $path): string
    {
        $realArchivePath = $filesystem->path($path);
        $result = $this->zipArchive->open($realArchivePath);

        if ($result !== true) {
            throw new \RuntimeException('Error unzipping archive: Result - '.$result);
        }

        $targetPath = basename($path, '.zip');
        $realTempPath = $filesystem->path($targetPath);
        $this->zipArchive->extractTo($realTempPath);

        return $targetPath.'/';
    }
}
