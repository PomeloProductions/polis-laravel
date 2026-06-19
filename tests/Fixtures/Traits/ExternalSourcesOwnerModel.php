<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Traits;

use Polis\Models\BaseModelAbstract;
use Polis\Traits\HasExternalSources;

/**
 * Test-only Eloquent model used to exercise the HasExternalSources trait.
 *
 * Has no production analogue — it's just a minimal table-backed model
 * whose only job is to provide a polymorphic owner for `sources` rows.
 * The trait's behaviour (relationship, upsert, force-delete on forget,
 * etc.) is fully exercised through CRUD on instances of this class.
 *
 * The fixture's table is created by the
 * 2026_06_19_000001_create_external_sources_fixture_table.php migration
 * under tests/Fixtures/database/migrations/, and loaded by
 * {@see ExternalSourcesTraitTestCase}.
 */
class ExternalSourcesOwnerModel extends BaseModelAbstract
{
    use HasExternalSources;

    protected $table = 'external_sources_owners';

    protected $guarded = [];
}
