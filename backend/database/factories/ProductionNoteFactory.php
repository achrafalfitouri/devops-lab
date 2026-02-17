<?php
namespace Database\Factories;

use App\Models\ProductionNote;
use App\Helpers\FactoryHelpers;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductionNoteFactory extends Factory
{
    protected $model = ProductionNote::class;

    public function definition()
    {
        $moroccanProductionComments = [
            'La production est en cours, nous vous tiendrons informés.',
            'L\'ordre de production a été lancé et est en phase de préparation.',
            'Les matériaux ont été préparés, la production commence demain.',
            'Le produit a été fabriqué et est prêt pour l\'expédition.',
            'Nous avons rencontré un léger retard, mais tout est sous contrôle.',
            'La production est terminée, le produit sera expédié bientôt.',
        ];

        return [
            'is_taxable' => $this->faker->boolean,
            'production_comment' => $this->faker->randomElement($moroccanProductionComments),
            'status' => $this->faker->randomElement(['Terminé', 'Retourné', 'Annulé', 'Perte']),
            'client_id' => FactoryHelpers::getRandomId(\App\Models\Client::class),
            'user_id' => FactoryHelpers::getRandomId(\App\Models\User::class),
            'order_note_id' => FactoryHelpers::getRandomId(\App\Models\OrderNote::class),
            'quote_id' => FactoryHelpers::getRandomId(\App\Models\Quote::class),

        ];
    }
}
