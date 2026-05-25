<?php

declare(strict_types=1);

namespace Polis\Console\Commands;

use App\Models\Messaging\Message;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Events\Messaging\MessageCreatedEvent;

/**
 * Class ResendMessage
 */
class ResendMessageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resend-message {message_id}';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'Resend Message';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resends a message based on its id';

    /**
     * @var MessageRepositoryContract
     */
    private $messageRepository;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * ResendPaymentReceipt constructor.
     */
    public function __construct(MessageRepositoryContract $messageRepository, Dispatcher $dispatcher)
    {
        parent::__construct();
        $this->messageRepository = $messageRepository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatches a payment created event
     */
    public function handle()
    {
        $messageId = $this->input->getArgument('message_id');

        /** @var Message $message */
        $message = $this->messageRepository->findOrFail($messageId);

        $event = new MessageCreatedEvent($message);

        $this->dispatcher->dispatch($event);

        $this->info('Message Scheduled');
    }
}
