<?php

declare(strict_types=1);

namespace Polis\Events\User\Contact;

use App\Models\User\Contact;

/**
 * Class ContactCreatedEvent
 */
class ContactCreatedEvent
{
    /**
     * @var Contact
     */
    private $contact;

    /**
     * ContactCreatedEvent constructor.
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }
}
