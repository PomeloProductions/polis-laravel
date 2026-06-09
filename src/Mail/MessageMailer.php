<?php

declare(strict_types=1);

namespace Polis\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Polis\Contracts\Models\Messaging\CanReceiveEmailsContract;
use Polis\Events\Messaging\MessageSentEvent;
use Polis\Models\Messaging\Message;

/**
 * Class NotificationMailer
 */
class MessageMailer extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * NotificationMailer constructor.
     */
    public function __construct(private CanReceiveEmailsContract $receiver, private Message $message)
    {
        $this->chain([new MessageSentEvent($message)]);
    }

    /**
     * Builds the email
     *
     * @return $this
     */
    public function build()
    {
        $email = $this->message->email ?? $this->receiver->getEmailAddress();
        $name = $this->receiver->getEmailToName();
        $data = $this->message->data;
        if (isset($data['message'])) {
            $data['message_content'] = $data['message'];
        }
        $message = $this->subject($this->message->subject)
            ->to($email, $name)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('mailers.'.$this->message->template, $data);

        if ($this->message->reply_to_email) {
            $message->replyTo($this->message->reply_to_email, $this->message->reply_to_name);
        }

        if (isset($this->message->data['attachments'])) {
            foreach ($this->message->data['attachments'] as $attachment) {
                $message->attach($attachment);
            }
        }

        return $message;
    }
}
