<?php

declare(strict_types=1);

namespace Polis\Contracts\Models\Messaging;

interface CanReceiveSMSContract extends CanReceiveMessageContract
{
    /**
     * Gets the phone number for routing SMS messages
     */
    public function getPhoneNumber(): ?string;
}
