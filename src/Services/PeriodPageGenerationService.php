<?php

declare(strict_types=1);

namespace Polis\Services;

use Illuminate\Support\Carbon;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\PeriodLadderContract;
use Polis\Contracts\Repositories\User\UserPageRepositoryContract;
use Polis\Contracts\Services\PeriodComponentCopierContract;
use Polis\Contracts\Services\PeriodGenerationServiceContract;
use Polis\Models\User\UserPage;

/**
 * Generic period-page generator — the domain-agnostic form of Todo's
 * TodoGenerationService. Given a {@see PeriodLadderContract} it walks the ladder
 * (year → month → week → day, or whatever the ladder defines), ensuring a page
 * exists at each level for a date and copying forward from the most recent
 * sibling. Per-component carry-forward is delegated to a
 * {@see PeriodComponentCopierContract} so the engine has zero knowledge of
 * component_type semantics.
 *
 * Owners are {@see IsAnEntityContract}, so any entity — not just a User — can
 * own a recurring hierarchy of period pages.
 */
class PeriodPageGenerationService implements PeriodGenerationServiceContract
{
    public function __construct(
        protected UserPageRepositoryContract $pageRepository,
        protected PeriodComponentCopierContract $componentCopier,
    ) {}

    public function ensureCurrentPeriods(PeriodLadderContract $ladder, IsAnEntityContract $entity, Carbon $date): UserPage
    {
        $rootPage = $this->findRootPage($ladder, $entity);
        if (! $rootPage) {
            throw new \RuntimeException('Entity does not have a root page for page type ['.$ladder->pageType().'].');
        }

        $parent = $rootPage;
        foreach ($ladder->levels() as $level) {
            $parent = $this->ensureLevelPage($ladder, $entity, $level, $date, $parent);
        }

        return $parent;
    }

    public function findRootPage(PeriodLadderContract $ladder, IsAnEntityContract $entity): ?UserPage
    {
        $pages = $this->pageRepository->findAll(
            array_merge($ladder->ownerFilter($entity), [
                ['page_type', '=', $ladder->pageType()],
            ]),
            [], [], [], null,
        );

        $rootMatch = $ladder->rootMatch();

        return $pages->first(fn (UserPage $page): bool => $this->configMatches($page, $rootMatch));
    }

    public function ensureLevelPage(
        PeriodLadderContract $ladder,
        IsAnEntityContract $entity,
        string $level,
        Carbon $date,
        UserPage $parentPage,
    ): UserPage {
        $match = $ladder->configMatchFor($entity, $level, $date);

        $existing = $this->findChildByConfig($parentPage, $ladder->pageType(), $match);
        if ($existing) {
            return $existing;
        }

        return $this->generateLevelPage($ladder, $entity, $level, $date, $parentPage);
    }

    protected function generateLevelPage(
        PeriodLadderContract $ladder,
        IsAnEntityContract $entity,
        string $level,
        Carbon $date,
        UserPage $parentPage,
    ): UserPage {
        $previous = $this->findMostRecentPage($ladder, $entity, $level);

        $attributes = array_merge(
            $ladder->pageAttributesFor($entity, $level, $date, $parentPage),
            $ladder->ownerAttributes($entity),
            ['page_type' => $ladder->pageType(), 'parent_page_id' => $parentPage->id],
        );

        /** @var UserPage $page */
        $page = $this->pageRepository->create($attributes);

        if ($previous) {
            $this->copyForwardComponents($previous, $page, $this->levelIsDateSpecific($ladder, $level) ? $date : null);
        }

        return $page;
    }

    /**
     * Copy every component of the source page onto the target page, delegating
     * the per-component carry-forward to the domain's component copier.
     */
    public function copyForwardComponents(UserPage $source, UserPage $target, ?Carbon $targetDate): void
    {
        $source->load('components');

        foreach ($source->components as $component) {
            $this->componentCopier->copyComponent($component, $target, $targetDate);
        }
    }

    /**
     * Whether copy-forward at this level should pass the concrete date through
     * to the component copier (so day-of-week scheduling applies). Only the
     * final/finest level is date-specific by convention.
     */
    protected function levelIsDateSpecific(PeriodLadderContract $ladder, string $level): bool
    {
        $levels = $ladder->levels();

        return $level === end($levels);
    }

    protected function findChildByConfig(UserPage $parent, string $pageType, array $configMatch): ?UserPage
    {
        $children = $this->pageRepository->findAll([
            ['parent_page_id', '=', $parent->id],
            ['page_type', '=', $pageType],
        ], [], [], [], null);

        return $children->first(fn (UserPage $page): bool => $this->configMatches($page, $configMatch));
    }

    protected function findMostRecentPage(PeriodLadderContract $ladder, IsAnEntityContract $entity, string $level): ?UserPage
    {
        $pages = $this->pageRepository->findAll(
            array_merge($ladder->ownerFilter($entity), [
                ['page_type', '=', $ladder->pageType()],
            ]),
            [], [], [], null,
        );

        // The level key is the same config key used by rootMatch (e.g. todo_level);
        // reuse it so the ladder does not have to expose it separately.
        $levelKey = array_key_first($ladder->rootMatch());

        return $pages
            ->filter(fn (UserPage $page): bool => ($page->config_json[$levelKey] ?? null) === $level)
            ->sortByDesc('id')
            ->first();
    }

    /**
     * @param  array<string, scalar>  $match
     */
    protected function configMatches(UserPage $page, array $match): bool
    {
        $config = $page->config_json ?? [];
        foreach ($match as $key => $value) {
            if (($config[$key] ?? null) != $value) {
                return false;
            }
        }

        return true;
    }
}
