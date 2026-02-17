<?php
namespace Database\Factories;

use App\Models\Invoice;
use App\Helpers\FactoryHelpers;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition()
    {
        $amount = $this->faker->numberBetween(100, 10000);
        $discount = $this->faker->randomFloat(2, 0, 500);
        $discountedAmount = max($amount - $discount, 0);
        $taxAmount = $this->faker->randomFloat(2, 0, $discountedAmount * 0.2);

        $moroccanComments = [
            'Facture émise pour le paiement des services.',
            'Le paiement doit être effectué dans les plus brefs délais.',
            'Merci de régler cette facture dans les 30 jours.',
            'Cette facture est due à la fin du mois.',
            'Montant à payer pour la livraison de la commande.',
            'Merci pour votre commande et paiement anticipé.',
        ];

        return [
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'discount' => $this->faker->randomFloat(2, 0, 500),
            'discounted_amount' => $this->faker->randomFloat(2, 100, 4500),
            'is_taxable' => $this->faker->boolean,
            'total_phrase' => $this->faker->sentence,
            'tax_amount' => $this->faker->randomFloat(2, 0, 500),
            'final_amount' => $this->faker->randomFloat(2, 100, 5000),
            'invoice_comment' => $this->faker->randomElement($moroccanComments),
            'client_id' => FactoryHelpers::getRandomId(\App\Models\Client::class),
            'user_id' => FactoryHelpers::getRandomId(\App\Models\User::class),
            'status' => 'Brouillon',
            'quote_id' => FactoryHelpers::getRandomId(\App\Models\Quote::class),

        ];
    }
}
