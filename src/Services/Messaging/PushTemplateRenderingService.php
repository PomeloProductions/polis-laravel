<?php

declare(strict_types=1);

namespace Polis\Services\Messaging;

use Polis\Contracts\Repositories\Messaging\PushTemplateRepositoryContract;
use Polis\Contracts\Services\Messaging\PushTemplateRenderingServiceContract;
use Polis\Exceptions\Messaging\TemplateNotFoundException;
use Polis\Push\RenderedPushNotification;

/**
 * Renders runtime-editable push notification templates into a final
 * title+body pair. Mirrors EmailTemplateRenderingService one-to-one.
 *
 * Lookup order (handled by PushTemplateRepository::findByKey + the
 * in-code fallback below):
 *   1. PushTemplate row where organization_id = $orgId AND key = $key
 *   2. PushTemplate row where organization_id IS NULL AND key = $key
 *   3. DefaultPushTemplates::TEMPLATES[$key]
 *   4. Throws TemplateNotFoundException
 *
 * Variable interpolation:
 *   - Blade-style `{{ var.path }}` syntax — preserved deliberately for
 *     consumer familiarity with Laravel and consistency with
 *     EmailTemplateRenderingService.
 *   - Resolves dotted paths via Laravel's data_get helper so e.g.
 *     `{{ user.profile.name }}` traverses nested arrays/objects.
 *   - Missing variables resolve to an empty string (no exception thrown);
 *     this matches Blade's default-render-nothing behavior.
 *
 * Differences from EmailTemplateRenderingService:
 *   - No HTML sanitization. Push notification bodies are plain text
 *     delivered to platform notification services (APNs/FCM/etc.) that
 *     do not interpret HTML, so the script/iframe/event-handler scrub
 *     is unnecessary.
 *   - No HTML-escaping of interpolated values. Again: the rendered
 *     string is treated as plain text by downstream consumers, so
 *     escaping characters like `<` to `&lt;` would only corrupt user
 *     input. If a downstream channel ever does interpret HTML, escaping
 *     should happen there, not here.
 */
class PushTemplateRenderingService implements PushTemplateRenderingServiceContract
{
    /**
     * @param  array<string, array{title: string, body: string}>  $defaultTemplates
     */
    public function __construct(
        private readonly PushTemplateRepositoryContract $repo,
        private readonly array $defaultTemplates,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function render(string $key, array $variables, ?int $organizationId = null): RenderedPushNotification
    {
        $template = $this->repo->findByKey($key, $organizationId);

        if ($template !== null) {
            $title = (string) ($template->getTitle() ?? '');
            $body = (string) ($template->getBody() ?? '');
        } elseif (isset($this->defaultTemplates[$key])) {
            $title = $this->defaultTemplates[$key]['title'] ?? '';
            $body = $this->defaultTemplates[$key]['body'] ?? '';
        } else {
            throw new TemplateNotFoundException("No push notification template found for key '{$key}'.");
        }

        return new RenderedPushNotification(
            title: $this->interpolate($title, $variables),
            body: $this->interpolate($body, $variables),
        );
    }

    /**
     * Substitute `{{ var.path }}` placeholders. Push notification bodies
     * are plain text — no HTML escape pass.
     *
     * @param  array<string, mixed>  $variables
     */
    private function interpolate(string $template, array $variables): string
    {
        $result = preg_replace_callback('/\{\{\s*([\w.]+)\s*\}\}/', function (array $matches) use ($variables) {
            $value = data_get($variables, $matches[1], '');
            if ($value === null) {
                $value = '';
            }
            if (is_bool($value)) {
                $value = $value ? '1' : '';
            }

            return is_scalar($value) ? (string) $value : '';
        }, $template);

        return $result ?? $template;
    }
}
