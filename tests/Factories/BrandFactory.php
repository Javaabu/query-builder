<?php

namespace Javaabu\QueryBuilder\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Javaabu\QueryBuilder\Tests\Models\Brand;

/**
 * @extends Factory
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'slug' => $this->faker->unique()->slug,
        ];
    }
}
