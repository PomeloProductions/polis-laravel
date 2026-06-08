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
 * Email-safe HTML: this service does NOT execute scripts. Template body HTML
 * (with variables already interpolated and escaped) is passed through
 * HTMLPurifier with an allowlist of email-safe tags and attributes. Anything
 * outside the allowlist — including <script>, <iframe>, <object>, <embed>,
 * <style>, event-handler attributes, and javascript:/vbscript:/data: URL
 * schemes — is removed.
 *
 * Untrusted variable values are additionally HTML-escaped before substitution
 * (interpolation pass) so that malicious values cannot inject markup at all;
 * HTMLPurifier then acts as a defense-in-depth pass over the final body.
 */
class EmailTemplateRenderingService implements EmailTemplateRenderingServiceContract
{
    /**
     * Cached HTMLPurifier_Config instance. Building the config from the
     * allowlist is the expensive step (HTMLPurifier parses the spec on
     * first build), so we reuse it across render() calls on the same
     * service instance.
     */
    private ?\HTMLPurifier_Config $purifierConfig = null;

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
     * Allowlist-based HTML sanitizer for email body output. Uses
     * ezyang/htmlpurifier to enforce a fixed set of email-safe tags and
     * attributes. Anything outside the allowlist (script/iframe/object/embed/
     * style tags, on* event handlers, javascript:/vbscript:/data: URLs,
     * unknown elements, etc.) is removed.
     */
    private function sanitizeHtml(string $html): string
    {
        $config = $this->purifierConfig ??= $this->buildPurifierConfig();
        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($html);
    }

    /**
     * Build (and cache on the instance) the HTMLPurifier configuration.
     * Disables the on-disk definition cache because emails are rendered
     * live and we don't want to require a writable filesystem path.
     */
    private function buildPurifierConfig(): \HTMLPurifier_Config
    {
        $config = \HTMLPurifier_Config::createDefault();
        // Skip on-disk cache; emails are rendered live and the package
        // should not require a writable cache dir on consumers.
        $config->set('Cache.DefinitionImpl', null);
        // Allowlist: standard email-safe tags + attributes.
        $config->set('HTML.Allowed',
            'p,br,strong,em,b,i,u,a[href|title|target],'
            .'h1,h2,h3,h4,h5,h6,'
            .'ul,ol,li,'
            .'blockquote,code,pre,'
            .'img[src|alt|title|width|height],'
            .'table,thead,tbody,tr,td,th'
        );
        // Force target=_blank + rel=noopener/nofollow on all anchors so a
        // hijacked link in a template body cannot navigate the host frame
        // or leak referrer-credentials to attacker-controlled pages.
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.Nofollow', true);

        return $config;
    }
}
