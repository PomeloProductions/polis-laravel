<?php

declare(strict_types=1);

namespace Polis\Testing;

use App\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Shipped helper that seeds the canonical role rows the policies and factories
 * reference. The schema migrations are create-only (no data), so a test that
 * exercises role-based authorization must seed these ids first. Call
 * {@see seedRoles()} from the consuming test case's `setUp()`.
 *
 * Seeds every id in {@see Role::ROLES} (which includes any
 * application-specific roles the consumer appended to that constant) plus the
 * organization/entity roles, using `insertOrIgnore` so it is safe to call more
 * than once and alongside a consumer's own seeders.
 */
trait SeedsRoles
{
    /**
     * Seed the fixed role rows referenced by policies + factories.
     */
    protected function seedRoles(): void
    {
        $ids = array_unique(array_merge(Role::ROLES, Role::ENTITY_ROLES));

        $rows = array_map(static function (int $id): array {
            return [
                'id' => $id,
                'name' => 'Role '.$id,
            ];
        }, $ids);

        DB::table('roles')->insertOrIgnore($rows);
    }
}
