<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Statistic;

use App\Models\Statistic\Statistic;
use App\Policies\Statistic\StatisticPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoPolicyParameters;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class ViewRequestAbstract
 */
abstract class ViewRequestAbstract extends BaseAuthenticatedRequestAbstract
{
    use HasNoPolicyParameters, HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return StatisticPolicy::ACTION_VIEW;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Statistic::class;
    }

    /**
     * {@inheritDoc}
     */
    public function allowedExpands(): array
    {
        return [
            'statisticFilters',
        ];
    }
}
