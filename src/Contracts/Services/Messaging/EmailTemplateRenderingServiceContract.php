<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Messaging;

use Polis\Exceptions\Messaging\TemplateNotFoundException;
use Polis\Mail\RenderedEmail;

/**
 * Interface EmailTemplateRenderingServiceContract
 */
interface EmailTemplateRenderingServiceContract
{
    /**
     * Render a template into a subject+body pair, interpolating the supplied
     * variables. See implementation for lookup-order details.
     *
     * @param  array<string, mixed>  $variables
     *
     * @throws TemplateNotFoundException
     */
    public function render(string $key, array $variables, ?int $organizationId = null): RenderedEmail;
}
