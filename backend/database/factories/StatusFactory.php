<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StatusFactory extends Factory
{
    protected $model = \App\Models\Status::class;

    public function definition()
    {
        $status = [
            "Actif",
            "Inactif",
            "En litige"

        ];
        return [
            'name' => $this->faker->randomElement($status)
        ];
    }
}
