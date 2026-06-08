<?php

declare(strict_types=1);

namespace Polis\Repositories\Messaging;

use Polis\Contracts\Messaging\EmailTemplateContract;
use Polis\Contracts\Repositories\Messaging\EmailTemplateRepositoryContract;
use Polis\Models\Messaging\EmailTemplate;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class EmailTemplateRepository
 *
 * Thin repository for the EmailTemplate model. Templates live in the
 * `articles` table (see EmailTemplate doc) so create/update inherit from
 * BaseRepositoryAbstract — the additive `key` and `organization_id` columns
 * are mass-assigned via the model's $fillable.
 *
 * The key method here is findByKey(), which implements the multi-tenant
 * lookup hierarchy: organization-scoped override → global default → null.
 */
class EmailTemplateRepository extends BaseRepositoryAbstract implements EmailTemplateRepositoryContract
{
    public function __construct(EmailTemplate $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }

    /**
     * {@inheritDoc}
     */
    public function findByKey(string $key, ?int $organizationId = null): ?EmailTemplateContract
    {
        if ($organizationId !== null) {
            /** @var EmailTemplate|null $template */
            $template = $this->model->newQuery()
                ->where('key', $key)
                ->where('organization_id', $organizationId)
                ->latest('updated_at')
                ->first();
            if ($template !== null) {
                return $template;
            }
        }

        /** @var EmailTemplate|null $template */
        $template = $this->model->newQuery()
            ->where('key', $key)
            ->whereNull('organization_id')
            ->latest('updated_at')
            ->first();

        return $template;
    }

    /**
     * {@inheritDoc}
     */
    public function findOrgScopedByKey(string $key, int $organizationId): ?EmailTemplateContract
    {
        /** @var EmailTemplate|null $template */
        $template = $this->model->newQuery()
            ->where('key', $key)
            ->where('organization_id', $organizationId)
            ->latest('updated_at')
            ->first();

        return $template;
    }

    /**
     * {@inheritDoc}
     */
    public function upsertOrgScoped(
        string $key,
        int $organizationId,
        string $subject,
        string $bodyHtml,
    ): EmailTemplateContract {
        /** @var EmailTemplate|null $existing */
        $existing = $this->model->newQuery()
            ->where('key', $key)
            ->where('organization_id', $organizationId)
            ->latest('updated_at')
            ->first();

        if ($existing !== null) {
            /** @var EmailTemplate $updated */
            $updated = $this->update($existing, [
                'title' => $subject,
                'last_iteration_content' => $bodyHtml,
            ]);

            return $updated;
        }

        /** @var EmailTemplate $created */
        $created = $this->create([
            'title' => $subject,
            'key' => $key,
            'organization_id' => $organizationId,
            'last_iteration_content' => $bodyHtml,
        ]);

        return $created;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteOrgScoped(string $key, int $organizationId): bool
    {
        $rows = $this->model->newQuery()
            ->where('key', $key)
            ->where('organization_id', $organizationId)
            ->get();

        if ($rows->isEmpty()) {
            return false;
        }

        foreach ($rows as $row) {
            $this->delete($row);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function listKeysForOrganization(int $organizationId): array
    {
        $keys = $this->model->newQuery()
            ->where(function ($q) use ($organizationId) {
                $q->whereNull('organization_id')
                    ->orWhere('organization_id', $organizationId);
            })
            ->whereNotNull('key')
            ->distinct()
            ->pluck('key')
            ->all();

        return array_values(array_map(static fn ($k) => (string) $k, $keys));
    }
}
