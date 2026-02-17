<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PayementTypesFactory extends Factory
{
    protected $model = \App\Models\PaymentType::class;

    public function definition()
    {
        $sectors = [
            "ESPECE",

            "VIREMENT",

            "CHEQUE",

            "EFFET"
        ];

        return [
            'name' => $this->faker->randomElement($sectors),
        ];
    }
}
