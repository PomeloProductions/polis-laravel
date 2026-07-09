<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

use Illuminate\Support\Carbon;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\PeriodLadderContract;
use Polis\Models\User\UserPage;

/**
 * Generates and back-fills the hierarchy of recurring period pages for an
 * entity, driven by a {@see PeriodLadderContract}. This is the generic form of
 * Todo's TodoGenerationService: it walks the ladder, ensuring a page exists at
 * every level for a date, copying forward from the most recent sibling page.
 *
 * It operates on {@see IsAnEntityContract} owners so Organizations (or any
 * entity) get recurring boards, not just users.
 */
interface PeriodGenerationServiceContract
{
    /**
     * Ensure every level's page exists for the entity on the given date,
     * generating (and copying forward) any that are missing. Returns the
     * finest-grained (last-level) page — for Todo, the day page.
     */
    public function ensureCurrentPeriods(PeriodLadderContract $ladder, IsAnEntityContract $entity, Carbon $date): UserPage;

    /**
     * Locate the persistent root page for the entity/ladder, or null.
     */
    public function findRootPage(PeriodLadderContract $ladder, IsAnEntityContract $entity): ?UserPage;

    /**
     * Ensure the page at a specific level exists (create + copy-forward if not).
     */
    public function ensureLevelPage(
        PeriodLadderContract $ladder,
        IsAnEntityContract $entity,
        string $level,
        Carbon $date,
        UserPage $parentPage,
    ): UserPage;
}
