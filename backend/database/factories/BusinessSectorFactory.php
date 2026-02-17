<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessSectorFactory extends Factory
{
    protected $model = \App\Models\BusinessSector::class;

    public function definition()
    {
        $sectors = [
            "Tourisme",
            "Restauration",
            "Transport",
            "Concessionnaire",
            "Education",
            "Medecine",
            "BTP",
            "Evenementiel",
            "Sous-traitance",
            "Textile",
            "Finance et légal",
            "Communication et marketing",
            "Beauté et bien-être",
            "Cosmétique",
            "Marché public",
            "Institutions et associations",
            "Autres services",
            "Autres produits"
        ];

        return [
            'name' => $this->faker->randomElement($sectors),
        ];
    }
}
