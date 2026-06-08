<?php

declare(strict_types=1);

namespace Polis\Repositories\Messaging;

use Polis\Contracts\Messaging\PushTemplateContract;
use Polis\Contracts\Repositories\Messaging\PushTemplateRepositoryContract;
use Polis\Models\Messaging\PushTemplate;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class PushTemplateRepository
 *
 * Thin repository for the PushTemplate model. Mirrors
 * EmailTemplateRepository one-to-one: templates live in the `articles`
 * table (see PushTemplate doc) so create/update inherit from
 * BaseRepositoryAbstract — the additive `key` and `organization_id`
 * columns are mass-assigned via the model's $fillable.
 *
 * The key method here is findByKey(), which implements the multi-tenant
 * lookup hierarchy: organization-scoped override → global default → null.
 */
class PushTemplateRepository extends BaseRepositoryAbstract implements PushTemplateRepositoryContract
{
    public function __construct(PushTemplate $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }

    /**
     * {@inheritDoc}
     */
    public function findByKey(string $key, ?int $organizationId = null): ?PushTemplateContract
    {
        if ($organizationId !== null) {
            /** @var PushTemplate|null $template */
            $template = $this->model->newQuery()
                ->where('key', $key)
                ->where('organization_id', $organizationId)
                ->latest('updated_at')
                ->first();
            if ($template !== null) {
                return $template;
            }
        }

        /** @var PushTemplate|null $template */
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
    public function findOrgScopedByKey(string $key, int $organizationId): ?PushTemplateContract
    {
        /** @var PushTemplate|null $template */
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
        string $title,
        string $body,
    ): PushTemplateContract {
        /** @var PushTemplate|null $existing */
        $existing = $this->model->newQuery()
            ->where('key', $key)
            ->where('organization_id', $organizationId)
            ->latest('updated_at')
            ->first();

        if ($existing !== null) {
            /** @var PushTemplate $updated */
            $updated = $this->update($existing, [
                'title' => $title,
                'last_iteration_content' => $body,
            ]);

            return $updated;
        }

        /** @var PushTemplate $created */
        $created = $this->create([
            'title' => $title,
            'key' => $key,
            'organization_id' => $organizationId,
            'last_iteration_content' => $body,
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
