<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GamutesFactory extends Factory
{
    protected $model = \App\Models\Gamutes::class;

    public function definition()
    {
        
        $elements = [
            "Bronze",
            "Silver",
            "Gold",
            "Platinium"

        ];

        return [
            'name' => $this->faker->randomElement($elements),
        ];
    }
}
