<?php
declare(strict_types=1);

namespace Database\Factories\Organization;
use App\Models\Organization\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class OrganizationFactory
 * @package Database\Factories\Organization
 */
class OrganizationFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = Organization::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
        ];
    }
}
