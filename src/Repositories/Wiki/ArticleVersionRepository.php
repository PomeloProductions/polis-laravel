<?php

declare(strict_types=1);

namespace Polis\Repositories\Wiki;

use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleVersion;
use Illuminate\Contracts\Events\Dispatcher;
use Polis\Contracts\Repositories\Wiki\ArticleVersionRepositoryContract;
use Polis\Events\Article\ArticleVersionCreatedEvent;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class ArticleVersionRepository
 */
class ArticleVersionRepository extends BaseRepositoryAbstract implements ArticleVersionRepositoryContract
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * ArticleVersionRepository constructor.
     */
    public function __construct(ArticleVersion $model, LogContract $log, Dispatcher $dispatcher)
    {
        parent::__construct($model, $log);
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param  Article|BaseModelAbstract|null  $relatedModel
     * @return BaseModelAbstract
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = []): BaseModelAbstract
    {
        $oldVersion = $relatedModel->current_version;
        /** @var ArticleVersion $newVersion */
        $newVersion = parent::create($data, $relatedModel, $forcedValues);

        $event = new ArticleVersionCreatedEvent($newVersion, $oldVersion);

        $this->dispatcher->dispatch($event);

        return $newVersion;
    }
}
