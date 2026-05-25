<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use App\Models\User\ArticleNote;
use Illuminate\Contracts\Events\Dispatcher;
use Polis\Contracts\Repositories\User\ArticleNoteRepositoryContract;
use Polis\Events\User\ArticleNoteCompletedEvent;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Traits\CanGetAndUnset;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class ArticleNoteRepository
 */
class ArticleNoteRepository extends BaseRepositoryAbstract implements ArticleNoteRepositoryContract
{
    use CanGetAndUnset;

    private Dispatcher $dispatcher;

    /**
     * ArticleNoteRepository constructor.
     */
    public function __construct(ArticleNote $model, LogContract $log, Dispatcher $dispatcher)
    {
        parent::__construct($model, $log);
        $this->dispatcher = $dispatcher;
    }

    /**
     * Override create to handle completed boolean
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = []): BaseModelAbstract
    {
        $completed = $this->getAndUnset($data, 'completed');

        if ($completed === true) {
            $data['completed_at'] = now();
        }

        $model = parent::create($data, $relatedModel, $forcedValues);

        if ($completed === true) {
            $this->dispatcher->dispatch(new ArticleNoteCompletedEvent($model));
        }

        return $model;
    }

    /**
     * Override update to handle completed boolean
     */
    public function update(BaseModelAbstract $model, array $data, array $forcedValues = []): BaseModelAbstract
    {
        $wasCompleted = $model->completed_at !== null;
        $completed = $this->getAndUnset($data, 'completed');

        if ($completed === true) {
            $data['completed_at'] = now();
        } elseif ($completed === false) {
            $data['completed_at'] = null;
        }

        $updated = parent::update($model, $data, $forcedValues);

        if ($completed === true && ! $wasCompleted) {
            $this->dispatcher->dispatch(new ArticleNoteCompletedEvent($updated));
        }

        return $updated;
    }
}
