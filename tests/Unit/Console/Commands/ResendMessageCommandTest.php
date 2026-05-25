<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Console\Commands;

use App\Models\Messaging\Message;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Polis\Console\Commands\ResendMessageCommand;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksConsoleOutput;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class ResendMessageCommandTest
 */
final class ResendMessageCommandTest extends TestCase
{
    use MocksConsoleOutput;

    /**
     * @var MessageRepositoryContract|array|LegacyMockInterface|MockInterface|CustomMockInterface
     */
    private $messageRepository;

    /**
     * @var array|Dispatcher|LegacyMockInterface|MockInterface|CustomMockInterface
     */
    private $dispatcher;

    private ResendMessageCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageRepository = mock(MessageRepositoryContract::class);
        $this->dispatcher = mock(Dispatcher::class);
        $this->command = new ResendMessageCommand($this->messageRepository, $this->dispatcher);
        $this->mockConsoleOutput($this->command);
    }

    public function test_handle(): void
    {
        $reflected = new \ReflectionClass($this->command);
        $input = $reflected->getProperty('input');
        $input->setAccessible(true);
        $mockInput = mock(InputInterface::class);
        $mockInput->shouldReceive('getArgument')->andReturn(4);
        $input->setValue($this->command, $mockInput);

        $this->messageRepository->shouldReceive('findOrFail')->andReturn(new Message);
        $this->dispatcher->shouldReceive('dispatch');

        $this->command->handle();
    }
}
