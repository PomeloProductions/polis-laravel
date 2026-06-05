<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Messaging;

use Polis\Exceptions\Messaging\TemplateNotFoundException;
use Polis\Push\RenderedPushNotification;

/**
 * Interface PushTemplateRenderingServiceContract
 *
 * Mirrors EmailTemplateRenderingServiceContract. Renders a runtime-editable
 * push notification template into its final title + body pair.
 */
interface PushTemplateRenderingServiceContract
{
    /**
     * Render a template into a title+body pair, interpolating the supplied
     * variables. See implementation for lookup-order details.
     *
     * @param  array<string, mixed>  $variables
     *
     * @throws TemplateNotFoundException
     */
    public function render(string $key, array $variables, ?int $organizationId = null): RenderedPushNotification;
}
