<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Statistic;

use App\Models\Statistic\Statistic;
use App\Policies\Statistic\StatisticPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoPolicyParameters;

/**
 * Class UpdateRequestAbstract
 */
abstract class UpdateRequestAbstract extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoPolicyParameters;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return StatisticPolicy::ACTION_UPDATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Statistic::class;
    }

    /**
     * The rules needed for the request
     */
    public function rules(Statistic $statistic): array
    {
        return $statistic->getValidationRules(Statistic::VALIDATION_RULES_UPDATE);
    }
}
