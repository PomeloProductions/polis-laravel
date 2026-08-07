<?php

declare(strict_types=1);

namespace Polis\Policies\Entity;

use App\Models\Role;
use App\Models\User\User;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Policies\BasePolicyAbstract;
use Polis\Policies\Wiki\ArticlePolicyAbstract;
use Polis\Policies\Wiki\OrganizationArticlePolicyAbstract;

/**
 * Class EntityArticlePolicyAbstract
 *
 * The entity-generic authorization base for the entity-scoped Article
 * ("contract") endpoints hung under `/{entity}/{entity_id}/articles`.
 *
 * This governs the opposite guarantee to the platform-wide
 * {@see ArticlePolicyAbstract} (whose `all`/`view` return
 * true for any authenticated user): an Article may only be listed/viewed
 * through an entity the caller actually manages.
 *
 * Authorization is expressed against {@see IsAnEntityContract} rather than a
 * concrete Organization, so the SAME policy serves a User-owned or an
 * Organization-owned article listing (and any future entity type). The entity
 * is passed to `all()`/`create()` as the route-bound parameter, exactly like
 * the entity Collection/Payment policies.
 *
 *   - platform super-admins  -> allowed for ANY entity
 *     (via {@see BasePolicyAbstract::before()}),
 *   - entity MANAGER / ADMINISTRATOR -> allowed for THEIR entity only
 *     (via IsAnEntityContract::canUserManageEntity()),
 *   - everyone else -> denied.
 *
 * The concrete cross-tenant boundary on view/update/delete (asserting the
 * article really belongs to the entity in the route) is entity-shape specific
 * and is layered on by concrete subclasses — see
 * {@see OrganizationArticlePolicyAbstract}, which asserts
 * `organization_id` against Article's `organization()` FK relation.
 */
abstract class EntityArticlePolicyAbstract extends BasePolicyAbstract
{
    /**
     * @var bool Whether management actions require the caller to be an
     *           ADMINISTRATOR of the entity rather than merely a MANAGER.
     */
    protected bool $requiresAdminForManagement = false;

    /**
     * The role required to manage (create/update/delete) an article.
     */
    protected function managementRole(): int
    {
        return $this->requiresAdminForManagement ? Role::ADMINISTRATOR : Role::MANAGER;
    }

    /**
     * A caller may list an entity's articles if they manage the entity.
     *
     * @return bool
     */
    public function all(User $user, IsAnEntityContract $entity)
    {
        return $entity->canUserManageEntity($user, Role::MANAGER);
    }

    /**
     * A caller may create an article for an entity they manage.
     *
     * @return bool
     */
    public function create(User $user, IsAnEntityContract $entity)
    {
        return $entity->canUserManageEntity($user, $this->managementRole());
    }
}
