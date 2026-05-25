<?php

declare(strict_types=1);

namespace Polis\Jobs;

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Helper\ProgressBar;

abstract class CanDisplayOutputAbstractJob
{
    /**
     * @var array|ProgressBar[]
     */
    private array $progressBars = [];

    public function __construct(protected ?OutputStyle $output = null) {}

    /**
     * Outputs a message if our output exists
     */
    public function outputMessage(string $message): void
    {
        $this->output?->text($message);
    }

    /**
     * Creates a progress bar for us. This must be done before advancing a progress bar
     */
    public function createProgress(string $name, int $steps): void
    {
        $this->progressBars[$name] = $this->output?->createProgressBar($steps);
    }

    /**
     * Advances a progress bar if it exists
     */
    public function advanceProgress(string $name): void
    {
        ($this->progressBars[$name] ?? null)?->advance();
    }
}
