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
}
