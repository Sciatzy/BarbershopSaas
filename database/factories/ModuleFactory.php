<?php

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $planId = 0;
        $planId = ($planId % 50) + 1;

        return [
            'name' => $this->faker->sentence(),
            'plan_id' => $planId,
        ];
    }
}
