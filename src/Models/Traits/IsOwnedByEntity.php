<?php

declare(strict_types=1);

namespace Polis\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Http\Core\Controllers\Entity\EntityResourceControllerAbstract;
use Polis\Policies\Entity\EntityResourcePolicyAbstract;
use Polis\Providers\BaseRepositoryProvider;

/**
 * Trait IsOwnedByEntity
 *
 * The counterpart to {@see IsEntity}. Where {@see IsEntity} is mixed into the
 * models that ARE an owning entity (User, Organization — implementers of
 * {@see IsAnEntityContract}), this trait is mixed into
 * the models that are OWNED BY an entity through the polymorphic
 * `owner_id` / `owner_type` columns — the exact shape Collection, Asset,
 * Payment, PaymentMethod and Subscription already use.
 *
 * It provides the single canonical `owner(): MorphTo` relation so an owned
 * resource resolves its owning entity generically (a User OR an Organization,
 * or any future entity type) without hard-coding a `belongsTo(User::class)` /
 * `belongsTo(Organization::class)`. This is what lets the reusable
 * {@see EntityResourceControllerAbstract}
 * and {@see EntityResourcePolicyAbstract} govern the
 * resource for any entity type.
 *
 * The `owner_type` column stores the morph alias returned by the entity's
 * `morphRelationName()` (e.g. `'organization'`, `'user'`) — the same aliases
 * registered in {@see BaseRepositoryProvider}'s morph map.
 *
 * @property int|null $owner_id
 * @property string|null $owner_type
 * @property-read Model|\Eloquent $owner
 */
trait IsOwnedByEntity
{
    /**
     * The entity (User / Organization / …) that owns this resource.
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
