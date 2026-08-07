<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Controllers\Entity;

use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Http\Core\Controllers\Entity\EntityArticleControllerAbstract;
use Polis\Http\Core\Requests\BaseRequestAbstract;

/**
 * Fixture exposing the entity-generic article listing for any entity type
 * (not just Organization), so the unit test can prove the base scopes by the
 * bound entity via belongsToArray regardless of the concrete entity class.
 */
class EntityArticleController extends EntityArticleControllerAbstract
{
    public function index(BaseRequestAbstract $request, IsAnEntityContract $entity)
    {
        return $this->indexForEntity($request, $entity);
    }
}
