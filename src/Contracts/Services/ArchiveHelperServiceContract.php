<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

use Illuminate\Filesystem\FilesystemAdapter;

interface ArchiveHelperServiceContract
{
    /**
     * Unzips the archive, and returns the path to the archive
     */
    public function unzipArchive(FilesystemAdapter $filesystem, string $path): string;
}
