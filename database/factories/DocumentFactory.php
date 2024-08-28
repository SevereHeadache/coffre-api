<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\SevereHeadache\Coffre\Models\Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $name = fake()->word(),
            'path' => $name,
            'value' => fake()->text(),
        ];
    }
}
