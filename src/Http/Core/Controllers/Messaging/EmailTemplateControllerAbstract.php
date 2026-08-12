<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Messaging;

use App\Models\Organization\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Polis\Contracts\Messaging\EmailTemplateContract;
use Polis\Contracts\Repositories\Messaging\EmailTemplateRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Requests\Messaging\EmailTemplate as Requests;
use Polis\Mail\DefaultEmailTemplates;

/**
 * Class EmailTemplateControllerAbstract
 *
 * Admin API for listing + editing org-scoped email templates. Consumers
 * subclass this and register routes such as:
 *
 *   Route::middleware('auth')->prefix('organizations/{organization}')
 *       ->group(function () {
 *           Route::get('email-templates', [EmailTemplateController::class, 'index'])
 *               ->name('organizations.email-templates.index');
 *           Route::get('email-templates/{key}', [EmailTemplateController::class, 'show'])
 *               ->name('organizations.email-templates.show');
 *           Route::put('email-templates/{key}', [EmailTemplateController::class, 'update'])
 *               ->name('organizations.email-templates.update');
 *           Route::delete('email-templates/{key}', [EmailTemplateController::class, 'destroy'])
 *               ->name('organizations.email-templates.destroy');
 *       });
 *
 * Lookup semantics matter for the response shape. For each key, the
 * resolved subject/body is the first non-null hit in:
 *     1. EmailTemplate row where organization_id = {organization.id}
 *     2. EmailTemplate row where organization_id IS NULL
 *     3. DefaultEmailTemplates::TEMPLATES[$key]
 *
 * The response always includes the in-code default copy alongside the
 * resolved values so the admin UI can diff/revert.
 *
 * Response shape (single template):
 *   {
 *     "key":              string,
 *     "subject":          string,   // resolved
 *     "body_html":        string,   // resolved
 *     "organization_id":  int|null, // null when sourced from global/default
 *     "source":           "org" | "global" | "default",
 *     "default_subject":  string,   // from DefaultEmailTemplates, "" if unknown key
 *     "default_body_html": string   // from DefaultEmailTemplates, "" if unknown key
 *   }
 */
abstract class EmailTemplateControllerAbstract extends BaseControllerAbstract
{
    public function __construct(
        protected EmailTemplateRepositoryContract $repository,
    ) {}

    /**
     * List every known template for the given organization. The union of
     * "known" keys is (DefaultEmailTemplates::TEMPLATES keys) U (all keys
     * that have any DB row, scoped to global + this org). Each entry is
     * resolved per the lookup hierarchy and shaped per the class docblock.
     */
    public function index(Requests\IndexRequest $request, Organization $organization): JsonResponse
    {
        $knownKeys = $this->collectKnownKeys($organization);

        $data = [];
        foreach ($knownKeys as $key) {
            $data[] = $this->buildTemplatePayload($key, $organization);
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Show a single template (resolved + default copy).
     */
    public function show(Requests\ViewRequest $request, Organization $organization, string $key): JsonResponse
    {
        return response()->json($this->buildTemplatePayload($key, $organization));
    }

    /**
     * Upsert the org-scoped template row for the given key. If an
     * org-scoped row already exists, it is updated in place; otherwise
     * a new row is created. The upsert + tabular handling are delegated
     * to the repository so the controller doesn't need to know about
     * the underlying article iteration pipeline.
     */
    public function update(Requests\UpdateRequest $request, Organization $organization, string $key): JsonResponse
    {
        $subject = (string) $request->input('subject');
        $bodyHtml = (string) $request->input('body_html');

        $this->repository->upsertOrgScoped($key, $organization->id, $subject, $bodyHtml);

        return response()->json($this->buildTemplatePayload($key, $organization));
    }

    /**
     * Revert: delete the org-scoped row so subsequent lookups fall
     * through to the global row (or the in-code default). Returns
     * 204 No Content per REST conventions. A request against a key
     * with no override is still 204 (idempotent).
     */
    public function destroy(Requests\DeleteRequest $request, Organization $organization, string $key): Response
    {
        $this->repository->deleteOrgScoped($key, $organization->id);

        return response()->noContent();
    }

    /**
     * Merge default keys and DB keys for the organization. Sorted for
     * stable response ordering — admin UI relies on this to render a
     * deterministic table.
     *
     * @return list<string>
     */
    protected function collectKnownKeys(Organization $organization): array
    {
        $defaultKeys = array_keys(DefaultEmailTemplates::TEMPLATES);
        $dbKeys = $this->repository->listKeysForOrganization($organization->id);

        $merged = array_values(array_unique(array_merge($defaultKeys, $dbKeys)));
        sort($merged);

        return $merged;
    }

    /**
     * Build the response payload for a single key, applying the
     * org -> global -> default lookup hierarchy.
     *
     * @return array<string, mixed>
     */
    protected function buildTemplatePayload(string $key, Organization $organization): array
    {
        $default = DefaultEmailTemplates::TEMPLATES[$key] ?? null;
        $defaultSubject = $default['subject'] ?? '';
        $defaultBodyHtml = $default['body_html'] ?? '';

        $orgRow = $this->repository->findOrgScopedByKey($key, $organization->id);

        if ($orgRow !== null) {
            return [
                'key' => $key,
                'subject' => $this->subjectOf($orgRow),
                'body_html' => $this->bodyHtmlOf($orgRow),
                'organization_id' => $organization->id,
                'source' => 'org',
                'default_subject' => $defaultSubject,
                'default_body_html' => $defaultBodyHtml,
            ];
        }

        // No org row — repository::findByKey internally falls back to the
        // global row when org-id was passed and no org-scoped row existed.
        $resolved = $this->repository->findByKey($key, $organization->id);

        if ($resolved !== null) {
            return [
                'key' => $key,
                'subject' => $this->subjectOf($resolved),
                'body_html' => $this->bodyHtmlOf($resolved),
                'organization_id' => null,
                'source' => 'global',
                'default_subject' => $defaultSubject,
                'default_body_html' => $defaultBodyHtml,
            ];
        }

        return [
            'key' => $key,
            'subject' => $defaultSubject,
            'body_html' => $defaultBodyHtml,
            'organization_id' => null,
            'source' => 'default',
            'default_subject' => $defaultSubject,
            'default_body_html' => $defaultBodyHtml,
        ];
    }

    private function subjectOf(EmailTemplateContract $tpl): string
    {
        return (string) ($tpl->getSubject() ?? '');
    }

    private function bodyHtmlOf(EmailTemplateContract $tpl): string
    {
        return (string) ($tpl->getBodyHtml() ?? '');
    }
}
