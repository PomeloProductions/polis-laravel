<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Polis\Console\BaseKernel;

/**
 * Class Kernel
 */
class Kernel extends BaseKernel
{
    /**
     * Gets the commands path for the child app
     */
    public function getAppCommandsPath(): string
    {
        return __DIR__.'/Commands';
    }

    protected function schedule(Schedule $schedule)
    {
        // No scheduled commands in the dummy consumer app. The Todo scheduling
        // is PolisOS-specific.
    }
}
