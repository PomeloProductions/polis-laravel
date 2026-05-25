<?php

declare(strict_types=1);

namespace Polis\Tests\Traits;

use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

trait MocksConsoleOutput
{
    /**
     * Replaces the instance of the output with something that will let everything pass
     */
    protected function mockConsoleOutput(Command $command)
    {
        $reflected = new \ReflectionClass($command);
        $output = $reflected->getProperty('output');
        $output->setAccessible(true);
        $mockOutput = mock(SymfonyStyle::class);

        $progressMock = mock(ProgressBar::class);
        $progressMock->shouldIgnoreMissing();

        $formatterMock = mock(OutputFormatterInterface::class);
        $formatterMock->shouldIgnoreMissing();

        $mockOutput->shouldIgnoreMissing($progressMock);
        $mockOutput->shouldReceive('getFormatter')->andReturn($formatterMock);

        $output->setValue($command, $mockOutput);
    }
}
