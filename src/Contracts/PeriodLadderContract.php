<?php

declare(strict_types=1);

namespace Polis\Contracts;

use Illuminate\Support\Carbon;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Models\User\UserPage;
use Polis\Services\PeriodPageGenerationService;

/**
 * Describes a hierarchy of recurring period "levels" (e.g. year → month → week
 * → day) and how a page is created for each level on a given date.
 *
 * This is the generic driver behind Todo's TodoGenerationService. A ladder
 * knows the ordered levels, how to match an existing page to a date at a level,
 * and what attributes a freshly generated page should carry. It is deliberately
 * ignorant of copy-forward mechanics and persistence — {@see PeriodPageGenerationService}
 * owns those and drives the ladder.
 *
 * Ladders operate on an owning {@see IsAnEntityContract} (a User, Organization,
 * …) so recurring boards are available to any entity, not just users.
 */
interface PeriodLadderContract
{
    /**
     * The page_type value that scopes all pages this ladder manages
     * (e.g. "todo").
     */
    public function pageType(): string;

    /**
     * Ordered list of level identifiers from coarsest to finest, EXCLUDING the
     * persistent root level (which is looked up, not generated). For Todo:
     * ['year', 'month', 'week', 'day'].
     *
     * @return list<string>
     */
    public function levels(): array;

    /**
     * The config-json key/value pairs that identify the ROOT page for an entity
     * (e.g. ['todo_level' => 'root']). Root pages are created out of band; the
     * service only locates them.
     *
     * @return array<string, scalar>
     */
    public function rootMatch(): array;

    /**
     * The where-filter (as repository filter tuples) that scopes page queries to
     * a single owning entity. This is the seam that lets the engine stay owner-
     * agnostic: today's Todo ladder maps an entity to [['user_id', '=', $id]];
     * a future organization-scoped ladder maps it to its own owner columns once
     * the page schema carries a polymorphic owner.
     *
     * @return list<array{0: string, 1: string, 2: mixed}>
     */
    public function ownerFilter(IsAnEntityContract $entity): array;

    /**
     * The owner attribute(s) stamped onto a page when it is created (e.g.
     * ['user_id' => $entity->getKey()]). Merged into pageAttributesFor() output
     * by the engine.
     *
     * @return array<string, mixed>
     */
    public function ownerAttributes(IsAnEntityContract $entity): array;

    /**
     * The config-json fingerprint that uniquely identifies the page at a level
     * for a given date, used both to find an existing page and (merged into
     * pageAttributesFor) to stamp a new one. For Todo `week` this might be
     * ['todo_level' => 'week', 'todo_week_start' => '2026-06-15'].
     *
     * @return array<string, scalar>
     */
    public function configMatchFor(IsAnEntityContract $entity, string $level, Carbon $date): array;

    /**
     * The full attribute array used to create a page at a level for a date. Must
     * include everything the page repository needs (name, slug, route_path,
     * display_order, config_json, …) EXCEPT the parent linkage and owner id,
     * which the service injects. `config_json` should contain configMatchFor().
     *
     * @return array<string, mixed>
     */
    public function pageAttributesFor(IsAnEntityContract $entity, string $level, Carbon $date, UserPage $parentPage): array;
}
