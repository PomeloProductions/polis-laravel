<?php
declare(strict_types=1);

namespace App\Console;

use Polis\Console\BaseKernel;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Class Kernel
 * @package App\Console
 */
class Kernel extends BaseKernel
{
    /**
     * Gets the commands path for the child app
     *
     * @return string
     */
    public function getAppCommandsPath(): string
    {
        return __DIR__.'/Commands';
    }

    /**
     * @param Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('todo:apply-daily-increments')->hourly();
        $schedule->command('todo:generate-daily')->hourly();
    }
}
