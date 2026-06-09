<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use App\Models\Role;
use App\Models\User\User;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Collection;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Traits\CanGetAndUnset;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class UserRepository
 */
class UserRepository extends BaseRepositoryAbstract implements UserRepositoryContract
{
    use CanGetAndUnset;

    private Hasher $hasher;

    private Config $config;

    /**
     * UserRepository constructor.
     */
    public function __construct(User $model, LogContract $log, Hasher $hasher, Config $config)
    {
        parent::__construct($model, $log);
        $this->hasher = $hasher;
        $this->config = $config;
    }

    /**
     * Overrides in order to hash the password properly
     *
     * @return BaseModelAbstract|User
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = []): BaseModelAbstract
    {
        $roles = $this->getAndUnset($data, 'roles', []);

        if (isset($data['password'])) {
            $data['password'] = $this->hasher->make($data['password']);
        }

        /** @var User $user */
        $user = parent::create($data, $relatedModel, $forcedValues);

        $user->roles()->sync($roles);

        return $user;
    }

    /**
     * Overrides in order to allow the syncing of roles
     *
     * @param  BaseModelAbstract|User  $model
     */
    public function update(BaseModelAbstract $model, array $data, array $forcedValues = []): BaseModelAbstract
    {
        $roles = $this->getAndUnset($data, 'roles', null);

        if (isset($data['password'])) {
            $data['password'] = $this->hasher->make($data['password']);
        }

        if ($roles !== null) {
            $model->roles()->sync($roles);
        }

        return parent::update($model, $data, $forcedValues);
    }

    /**
     * Attempts to look up a user by email address, and returns null if we cannot find one
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Finds all system users in the system
     *
     * Creates a new user if one is not found
     */
    public function findSuperAdmins(): Collection
    {
        /** @var Collection $users */
        $users = $this->model->whereHas('roles', function ($query) {
            $query->where('role_id', Role::SUPER_ADMIN);
        })->get();

        if ($users->count() == 0) {
            /** @var User $user */
            $user = $this->create([
                'first_name' => $this->config->get('mail.from.name'),
                'email' => $this->config->get('mail.from.email'),
            ]);

            $user->roles()->attach(Role::SUPER_ADMIN);

            return new Collection([$user]);
        }

        return $users;
    }

    /**
     * Swagger definitions below...
     *
     * @SWG\Definition(
     *     definition="Users",
     *
     *     @SWG\Property(
     *         property="data",
     *         description="A list of user models",
     *         type="array",
     *         minItems=0,
     *         maxItems=100,
     *         uniqueItems=true,
     *
     *         @SWG\Items(ref="#/definitions/User")
     *     )
     * )
     */
}
