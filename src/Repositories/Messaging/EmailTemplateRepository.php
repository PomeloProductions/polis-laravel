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
}
