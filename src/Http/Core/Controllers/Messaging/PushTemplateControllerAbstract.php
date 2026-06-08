<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Messaging;

use App\Http\Core\Requests\Messaging\PushTemplate as Requests;
use App\Models\Organization\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Polis\Contracts\Messaging\PushTemplateContract;
use Polis\Contracts\Repositories\Messaging\PushTemplateRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Push\DefaultPushTemplates;

/**
 * Class PushTemplateControllerAbstract
 *
 * Admin API for listing + editing org-scoped push templates. Mirrors
 * EmailTemplateControllerAbstract one-to-one; the only structural
 * difference is the payload field names — push templates expose
 * `title` + `body` instead of `subject` + `body_html`.
 *
 * Suggested route registration (consumer-side):
 *
 *   Route::middleware('auth')->prefix('organizations/{organization}')
 *       ->group(function () {
 *           Route::get('push-templates', [PushTemplateController::class, 'index'])
 *               ->name('organizations.push-templates.index');
 *           Route::get('push-templates/{key}', [PushTemplateController::class, 'show'])
 *               ->name('organizations.push-templates.show');
 *           Route::put('push-templates/{key}', [PushTemplateController::class, 'update'])
 *               ->name('organizations.push-templates.update');
 *           Route::delete('push-templates/{key}', [PushTemplateController::class, 'destroy'])
 *               ->name('organizations.push-templates.destroy');
 *       });
 *
 * Response shape (single template):
 *   {
 *     "key":             string,
 *     "title":           string,   // resolved
 *     "body":            string,   // resolved
 *     "organization_id": int|null,
 *     "source":          "org" | "global" | "default",
 *     "default_title":   string,
 *     "default_body":    string
 *   }
 */
abstract class PushTemplateControllerAbstract extends BaseControllerAbstract
{
    public function __construct(
        protected PushTemplateRepositoryContract $repository,
    ) {}

    public function index(Requests\IndexRequest $request, Organization $organization): JsonResponse
    {
        $knownKeys = $this->collectKnownKeys($organization);

        $data = [];
        foreach ($knownKeys as $key) {
            $data[] = $this->buildTemplatePayload($key, $organization);
        }

        return response()->json(['data' => $data]);
    }

    public function show(Requests\ViewRequest $request, Organization $organization, string $key): JsonResponse
    {
        return response()->json($this->buildTemplatePayload($key, $organization));
    }

    public function update(Requests\UpdateRequest $request, Organization $organization, string $key): JsonResponse
    {
        $title = (string) $request->input('title');
        $body = (string) $request->input('body');

        $this->repository->upsertOrgScoped($key, $organization->id, $title, $body);

        return response()->json($this->buildTemplatePayload($key, $organization));
    }

    public function destroy(Requests\DeleteRequest $request, Organization $organization, string $key): Response
    {
        $this->repository->deleteOrgScoped($key, $organization->id);

        return response()->noContent();
    }

    /**
     * @return list<string>
     */
    protected function collectKnownKeys(Organization $organization): array
    {
        $defaultKeys = array_keys(DefaultPushTemplates::TEMPLATES);
        $dbKeys = $this->repository->listKeysForOrganization($organization->id);

        $merged = array_values(array_unique(array_merge($defaultKeys, $dbKeys)));
        sort($merged);

        return $merged;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildTemplatePayload(string $key, Organization $organization): array
    {
        $default = DefaultPushTemplates::TEMPLATES[$key] ?? null;
        $defaultTitle = $default['title'] ?? '';
        $defaultBody = $default['body'] ?? '';

        $orgRow = $this->repository->findOrgScopedByKey($key, $organization->id);

        if ($orgRow !== null) {
            return [
                'key' => $key,
                'title' => $this->titleOf($orgRow),
                'body' => $this->bodyOf($orgRow),
                'organization_id' => $organization->id,
                'source' => 'org',
                'default_title' => $defaultTitle,
                'default_body' => $defaultBody,
            ];
        }

        $resolved = $this->repository->findByKey($key, $organization->id);

        if ($resolved !== null) {
            return [
                'key' => $key,
                'title' => $this->titleOf($resolved),
                'body' => $this->bodyOf($resolved),
                'organization_id' => null,
                'source' => 'global',
                'default_title' => $defaultTitle,
                'default_body' => $defaultBody,
            ];
        }

        return [
            'key' => $key,
            'title' => $defaultTitle,
            'body' => $defaultBody,
            'organization_id' => null,
            'source' => 'default',
            'default_title' => $defaultTitle,
            'default_body' => $defaultBody,
        ];
    }

    private function titleOf(PushTemplateContract $tpl): string
    {
        return (string) ($tpl->getTitle() ?? '');
    }

    private function bodyOf(PushTemplateContract $tpl): string
    {
        return (string) ($tpl->getBody() ?? '');
    }
}
