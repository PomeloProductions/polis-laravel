<?php
namespace Database\Factories\Organization;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class OrganizationManagerFactory
 * @package Database\Factories\Organization
 */
class OrganizationManagerFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = OrganizationManager::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory()->create()->id,
            'organization_id' => Organization::factory()->create()->id,
            'role_id' => \App\Models\Role::ADMINISTRATOR,
        ];
    }
}
