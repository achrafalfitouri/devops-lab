<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TitlesFactory extends Factory
{
    protected $model = \App\Models\Titles::class;

    public function definition()
    {
        $title = [
            "Chef d'impression et finition",
            "Équipe d'impression et finition",
            "Chef de couture et broderie",
            "Équipe de couture et broderie",
            "Chef packaging",
            "Équipe packaging",
            "Chef signalisation et habillage",
            "Équipe de signalisation et habillage",
            "Responsable RH",
            "Responsable Finance et comptabilité",
            "Responsable Facturation",
            "Responsable Achat",
            "Responsable commercial & marketing",
            "Responsable Recouvrement",
            "Responsable Caisse",
            "Responsable de stock",
            "Responsable administrative",
            "Direction général"
        ];
        return [
            'name' => $this->faker->randomElement($title)
        ];
    }
}
