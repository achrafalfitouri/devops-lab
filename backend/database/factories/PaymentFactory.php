<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        $getRandomId = function ($modelClass) {
            $record = $modelClass::inRandomOrder()->first();
            return $record ? $record->id : null; 
        };

        $moroccanComments = [
            'Paiement effectué pour la facture du mois de janvier.',
            'Montant payé pour la commande de produits.',
            'Réglé par virement bancaire selon les termes convenus.',
            'Merci pour le paiement rapide, votre commande est confirmée.',
            'Le paiement a été reçu pour les services rendus.',
            'Le paiement a été traité pour le mois de février.',
        ];

        return [
            'date' => $this->faker->date(),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'comment' => $this->faker->randomElement($moroccanComments),
            'payment_type_id' => $getRandomId(PaymentType::class),
            'invoice_id' => $getRandomId(Invoice::class),
            'check_number' => $this->faker->regexify('[A-Z0-9]{10}'),
            'wire_transfer_number' => $this->faker->regexify('[A-Z0-9]{15}'),
            'effect_number' => $this->faker->regexify('[A-Z0-9]{8}'),
        ];
    }
}
