<?php

declare(strict_types=1);

namespace Polis\Services;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Polis\Contracts\Services\DirectoryCopyServiceContract;

class DirectoryCopyService implements DirectoryCopyServiceContract
{
    /**
     * Copies a directory from one source to another
     *
     * @return void
     *
     * @throws FileNotFoundException
     */
    public function copyDirectory(FilesystemAdapter $from, FilesystemAdapter $to, string $fromPath, string $toPath)
    {
        foreach ($from->files($fromPath) as $file) {
            $to->put($toPath.basename($file), $from->get($file));
        }
        foreach ($from->directories($fromPath) as $directory) {
            $target = $toPath.basename($directory);
            $to->createDir($target);
            $this->copyDirectory($from, $to, $directory, $target.'/');
        }
    }
}
