<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientTypeFactory extends Factory
{
    protected $model = \App\Models\ClientType::class;

    public function definition()
    {
        $types = [
            "Particulier",
            "Entreprise",
            "Institution"
        ];
        return [
            'name' => $this->faker->randomElement($types)
        ];
    }
}
