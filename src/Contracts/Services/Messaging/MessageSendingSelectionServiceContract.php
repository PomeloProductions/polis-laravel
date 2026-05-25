<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Messaging;

interface MessageSendingSelectionServiceContract
{
    /**
     * Gets the service based on the passed in name
     */
    public function getSendingService(string $name): ?BaseMessageSendingServiceContract;
}
