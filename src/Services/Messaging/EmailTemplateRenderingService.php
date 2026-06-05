<?php

declare(strict_types=1);

namespace Polis\Services\Messaging;

use Polis\Contracts\Repositories\Messaging\EmailTemplateRepositoryContract;
use Polis\Contracts\Services\Messaging\EmailTemplateRenderingServiceContract;
use Polis\Exceptions\Messaging\TemplateNotFoundException;
use Polis\Mail\RenderedEmail;

/**
 * Renders runtime-editable email templates into a final subject+body pair.
 *
 * Lookup order (handled by EmailTemplateRepository::findByKey + the
 * in-code fallback below):
 *   1. EmailTemplate row where organization_id = $orgId AND key = $key
 *   2. EmailTemplate row where organization_id IS NULL AND key = $key
 *   3. DefaultEmailTemplates::TEMPLATES[$key]
 *   4. Throws TemplateNotFoundException
 *
 * Variable interpolation:
 *   - Blade-style `{{ var.path }}` syntax — preserved deliberately for
 *     consumer familiarity with Laravel.
 *   - Resolves dotted paths via Laravel's data_get helper so e.g.
 *     `{{ user.profile.name }}` traverses nested arrays/objects.
 *   - Missing variables resolve to an empty string (no exception thrown);
 *     this matches Blade's default-render-nothing behavior.
 *
 * We do NOT invoke the full Blade compiler here. Rationale: Blade is built
 * around file-backed views, and compiling a dynamic string requires either
 * a custom view resolver or `Blade::render()` (which itself caches via
 * temp files). For v0.2 a simple regex interpolator is sufficient, faster,
 * and trivially testable — and the syntax remains Blade-compatible should
 * we choose to upgrade later.
 *
 * Email-safe HTML: this service does NOT execute scripts. Both literal
 * template content AND interpolated variable values are passed through a
 * conservative HTML sanitizer before being emitted, which strips:
 *   - <script>, <iframe>, <object>, <embed>, <style> tags
 *   - event-handler attributes (onclick=, onload=, etc.)
 *   - javascript:, data:, vbscript: URLs
 *
 * Template authors should still favor a small allowlist of safe HTML
 * (paragraphs, links, basic formatting). Untrusted variable values are
 * additionally HTML-escaped before substitution to prevent script injection
 * through interpolation.
 */
class EmailTemplateRenderingService implements EmailTemplateRenderingServiceContract
{
    /**
     * @param  array<string, array{subject: string, body_html: string}>  $defaultTemplates
     */
    public function __construct(
        private readonly EmailTemplateRepositoryContract $repo,
        private readonly array $defaultTemplates,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function render(string $key, array $variables, ?int $organizationId = null): RenderedEmail
    {
        $template = $this->repo->findByKey($key, $organizationId);

        if ($template !== null) {
            $subject = (string) ($template->getSubject() ?? '');
            $bodyHtml = (string) ($template->getBodyHtml() ?? '');
        } elseif (isset($this->defaultTemplates[$key])) {
            $subject = $this->defaultTemplates[$key]['subject'] ?? '';
            $bodyHtml = $this->defaultTemplates[$key]['body_html'] ?? '';
        } else {
            throw new TemplateNotFoundException("No email template found for key '{$key}'.");
        }

        return new RenderedEmail(
            subject: $this->interpolate($subject, $variables),
            bodyHtml: $this->sanitizeHtml($this->interpolate($bodyHtml, $variables, escape: true)),
        );
    }

    /**
     * Substitute `{{ var.path }}` placeholders. When $escape is true,
     * resolved values are HTML-escaped before substitution (used for body).
     *
     * @param  array<string, mixed>  $variables
     */
    private function interpolate(string $template, array $variables, bool $escape = false): string
    {
        $result = preg_replace_callback('/\{\{\s*([\w.]+)\s*\}\}/', function (array $matches) use ($variables, $escape) {
            $value = data_get($variables, $matches[1], '');
            if ($value === null) {
                $value = '';
            }
            if (is_bool($value)) {
                $value = $value ? '1' : '';
            }
            $string = is_scalar($value) ? (string) $value : '';

            return $escape ? htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $string;
        }, $template);

        return $result ?? $template;
    }

    /**
     * Conservative HTML sanitizer for email body output. Strips dangerous
     * elements and attributes. NOT a full HTML allowlist — template authors
     * should still write only safe HTML.
     */
    private function sanitizeHtml(string $html): string
    {
        // Strip dangerous tags (script/iframe/object/embed/style) including content
        $html = preg_replace('#<(script|iframe|object|embed|style)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        // Strip self-closing/unterminated dangerous tags
        $html = preg_replace('#<(script|iframe|object|embed|style)\b[^>]*>#i', '', $html) ?? $html;
        // Strip on* event handlers (onclick=..., onload=..., etc.)
        $html = preg_replace('#\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? $html;
        // Strip javascript:/vbscript:/data: URL schemes (defang)
        $html = preg_replace('#(href|src|action|formaction|xlink:href)\s*=\s*("|\')\s*(?:javascript|vbscript|data)\s*:#i', '$1=$2#', $html) ?? $html;

        return $html;
    }
}
