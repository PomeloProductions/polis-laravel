<?php

declare(strict_types=1);

namespace Polis\Services\Messaging;

use Polis\Contracts\Services\Messaging\BaseMessageSendingServiceContract;
use Polis\Contracts\Services\Messaging\MessageSendingSelectionServiceContract;

class MessageSendingSelectionService implements MessageSendingSelectionServiceContract
{
    /**
     * @param  array|BaseMessageSendingServiceContract[]  $services  All services that are enabled
     */
    public function __construct(private array $services) {}

    /**
     * Gets the service based on the passed in name
     */
    public function getSendingService(string $name): ?BaseMessageSendingServiceContract
    {
        return $this->services[$name] ?? null;
    }
}
