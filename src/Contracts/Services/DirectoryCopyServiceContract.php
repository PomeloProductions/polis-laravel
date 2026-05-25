<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;

interface DirectoryCopyServiceContract
{
    /**
     * Copies a directory from one source to another
     *
     * @return void
     *
     * @throws FileNotFoundException
     */
    public function copyDirectory(FilesystemAdapter $from, FilesystemAdapter $to, string $fromPath, string $toPath);
}
