<?php

declare(strict_types=1);

namespace Polis\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Polis\Contracts\Services\Messaging\EmailTemplateRenderingServiceContract;

/**
 * Mailable that defers subject + body resolution to the runtime-editable
 * email template system (see EmailTemplateRenderingService).
 *
 * Callers no longer pass a hardcoded subject + view name. Instead they pass
 * a stable string key (e.g. 'welcome') and a flat array of variables, and
 * the mailable looks up the appropriate org-scoped or global template at
 * send time.
 *
 *     Mail::to($user)->send(new TemplatedMailable(
 *         templateKey: 'welcome',
 *         variables: [
 *             'user' => $user->toArray(),
 *             'app' => ['name' => config('app.name')],
 *         ],
 *         organizationId: $organization?->id,
 *     ));
 */
class TemplatedMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $variables  Flat data passed to the
     *                                           interpolator. Use `{{ user.first_name }}` etc. in the template body
     *                                           to reference nested values.
     */
    public function __construct(
        public readonly string $templateKey,
        public readonly array $variables,
        public readonly ?int $organizationId = null,
    ) {}

    /**
     * Resolve the template via the rendering service, then hand the final
     * subject + body off to Laravel's Mailable.
     */
    public function build(): self
    {
        $rendered = app(EmailTemplateRenderingServiceContract::class)
            ->render($this->templateKey, $this->variables, $this->organizationId);

        return $this->subject($rendered->subject)
            ->html($rendered->bodyHtml);
    }
}
