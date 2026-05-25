<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Jobs;

use Illuminate\Console\OutputStyle;
use Polis\Tests\Mocks\CanDisplayOutputJob;
use Polis\Tests\TestCase;
use Symfony\Component\Console\Helper\ProgressBar;

final class CanDisplayOutputAbstractJobTest extends TestCase
{
    public function test_out_message_does_nothing_without_output(): void
    {
        $job = new CanDisplayOutputJob;

        $job->outputMessage('hello');
    }

    public function test_out_message_writes_when_output_exists(): void
    {
        $output = mock(OutputStyle::class);

        $job = new CanDisplayOutputJob($output);

        $output->shouldReceive('text');

        $job->outputMessage('hello');
    }

    public function test_create_progress_bar_does_nothing_without_output(): void
    {
        $job = new CanDisplayOutputJob;

        $job->createProgress('progress', 100);
    }

    public function test_create_progress_bar_when_output_exists(): void
    {
        $output = mock(OutputStyle::class);

        $job = new CanDisplayOutputJob($output);

        $output->shouldReceive('isDecorated')->andReturn(false);

        $progress = mock(new ProgressBar($output));
        $output->shouldReceive('createProgressBar')->andReturn($progress);

        $job->createProgress('progress', 100);
    }

    public function test_advance_progress_does_nothing_before_creation(): void
    {
        $output = mock(OutputStyle::class);

        $job = new CanDisplayOutputJob($output);

        $output->shouldReceive('isDecorated')->andReturn(false);

        $job->advanceProgress('progress');
    }

    public function test_advance_progress_interacts_properly(): void
    {
        $output = mock(OutputStyle::class);

        $job = new CanDisplayOutputJob($output);

        $output->shouldReceive('isDecorated')->andReturn(false);

        $progress = mock(new ProgressBar($output));
        $output->shouldReceive('createProgressBar')->andReturn($progress);
        $progress->shouldReceive('advance');

        $job->advanceProgress('progress');
    }
}
