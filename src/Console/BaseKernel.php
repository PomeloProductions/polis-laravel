<?php

declare(strict_types=1);

namespace Polis\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

abstract class BaseKernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load([
            $this->getAppCommandsPath(),
            __DIR__.'/Commands',
        ]);
    }

    /**
     * Gets the commands path for the child app
     */
    abstract public function getAppCommandsPath(): string;
}
