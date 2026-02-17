<?php

namespace Database\Factories;

use App\Models\CashTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\CashRegister;
use App\Models\CashTransactionType;

class CashTransactionFactory extends Factory
{
    protected $model = CashTransaction::class;

    private static $transactionNumber = 3200;

    public function definition(): array
    {
        $yearSuffix = now()->format('y');
        
        $code = 'CAT-' . $yearSuffix . '-' . self::$transactionNumber++;

        $getRandomId = function ($modelClass) {
            $record = $modelClass::inRandomOrder()->first();
            return $record ? $record->id : null;
        };

        $moroccanTransactionNames = [
            'Paiement fournisseur',
            'Encaissement client',
            'Achat de fournitures',
            'Paiement de facture',
            'Remboursement prêt',
            'Vente comptoir',
            'Frais de transport'
        ];

        return [
            'id' => Str::uuid(),
            'amount' => $this->faker->numberBetween(100, 1000),
            'name' => $this->faker->randomElement($moroccanTransactionNames),
            'date' => $this->faker->date(),
            'code' => $code,
            'comment' => $this->faker->optional()->sentence,
            'cash_register_id' => $getRandomId(CashRegister::class),
            'cash_transaction_type_id' => $getRandomId(CashTransactionType::class),
        ];
    }
}
