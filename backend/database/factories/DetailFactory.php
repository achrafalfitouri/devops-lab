<?php

namespace Database\Factories;

use App\Models\Detail;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Detail::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $moroccanNames = ['Produit', 'Service', 'Livraison', 'Facture', 'Commande'];
        $moroccanDetails = [
            'Détails concernant la livraison de produit.',
            'Facture de l\'acheteur à vérifier.',
            'Service de maintenance après-vente.',
            'Commande prête pour expédition.',
            'Fournisseur de produit confirmé.'
        ];

        return [
            'name' => $this->faker->randomElement($moroccanNames),
            'detail' => $this->faker->randomElement($moroccanDetails),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
  

}
