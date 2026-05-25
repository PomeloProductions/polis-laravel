<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\Messaging;

use App\Models\Messaging\Message;
use App\Models\User\User;
use Illuminate\Support\Collection;
use Polis\Contracts\Models\CanReceiveTextMessagesContract;
use Polis\Contracts\Repositories\BaseRepositoryContract;

/**
 * Interface MessageRepositoryContract
 */
interface MessageRepositoryContract extends BaseRepositoryContract
{
    /**
     * Sends an email directly to a user
     */
    public function sendEmailToUser(
        User $user,
        string $subject,
        string $template,
        array $baseTemplateData = [],
        ?string $greeting = null,
        array $via = [Message::VIA_EMAIL],
    ): Message;

    /**
     * Sends an email directly to the main system users in the system
     */
    public function sendEmailToSuperAdmins(
        string $subject,
        string $template,
        array $baseTemplateData = [],
        ?string $greeting = null,
        array $via = [Message::VIA_EMAIL],
    ): Collection;

    /**
     * Sends an email directly to the passed in email without linking to a model
     *
     * @param  string|null  $greeting
     */
    public function sendDirectEmail(string $email, string $subject, string $template, string $greeting, array $baseTemplateData = []): Message;

    /**
     * Sends a text message to a related model
     */
    public function sendTextMessage(CanReceiveTextMessagesContract $model, string $message): Message;
}
