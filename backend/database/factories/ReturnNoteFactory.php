<?php
namespace Database\Factories;

use App\Models\ReturnNote;
use App\Helpers\FactoryHelpers;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReturnNoteFactory extends Factory
{
    protected $model = ReturnNote::class;

    public function definition()
    {
        $moroccanReturnComments = [
            'Retour accepté, le produit sera échangé sous peu.',
            'Le retour a été approuvé, la marchandise sera envoyée demain.',
            'La demande de retour a été traitée, nous attendons la confirmation de l\'expédition.',
            'Retour refusé en raison de l\'état du produit.',
            'Produit retourné, en attente de validation.',
            'Retour partiellement accepté, une partie des produits sera remboursée.',
        ];

        return [
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'is_taxable' => $this->faker->boolean,
            'tax_amount' => $this->faker->randomFloat(2, 0, 500),
            'final_amount' => $this->faker->randomFloat(2, 100, 5000),
            'total_phrase' => $this->faker->randomFloat(2, 100, 5000),
            'return_comment' => $this->faker->randomElement($moroccanReturnComments),
            'status' => 'Brouillon',
            'delivery_note_id' => FactoryHelpers::getRandomId(\App\Models\DeliveryNote::class),
            'client_id' => FactoryHelpers::getRandomId(\App\Models\Client::class),
            'user_id' => FactoryHelpers::getRandomId(\App\Models\User::class),
            'quote_id' => FactoryHelpers::getRandomId(\App\Models\Quote::class),

        ];
    }
}
