<?php

namespace Database\Factories;

use App\DataSourceKind;
use App\Models\DataSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataSource>
 */
class DataSourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->lexify('source-??????'),
            'name' => fake()->words(2, true),
            'kind' => fake()->randomElement(DataSourceKind::cases()),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
