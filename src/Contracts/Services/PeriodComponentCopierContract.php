<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

use Illuminate\Support\Carbon;
use Polis\Models\User\UserPage;
use Polis\Models\User\UserPageComponent;
use Polis\Services\PeriodPageGenerationService;

/**
 * Copies a single component from a source period page onto a freshly generated
 * target period page, applying whatever carry-forward rules the domain needs.
 *
 * {@see PeriodPageGenerationService} owns page creation and the
 * ladder walk but knows nothing about component_type semantics; it delegates
 * the per-component copy to an implementation of this contract. Todo supplies
 * one that handles `todo_task` (relational tree copy) and config-json copy
 * rules for other component types.
 */
interface PeriodComponentCopierContract
{
    /**
     * Copy one source component onto the target page.
     *
     * @param  UserPageComponent  $source  The component being carried forward.
     * @param  UserPage  $targetPage  The newly generated page it is copied onto.
     * @param  Carbon|null  $targetDate  The date of the target page (null for
     *                                   coarser levels that are not date-specific
     *                                   enough to drive scheduling).
     */
    public function copyComponent(UserPageComponent $source, UserPage $targetPage, ?Carbon $targetDate): void;
}
