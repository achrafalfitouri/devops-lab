<?php

namespace Database\Factories;

use App\Models\CashTransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CashTransactionTypeFactory extends Factory
{
    protected $model = CashTransactionType::class;

    public function definition(): array
    {
        $moroccanTransactionTypes = ['Dépôt', 'Retrait', 'Paiement', 'Encaissement'];

        return [
            'id' => Str::uuid(),
            'name' => $this->faker->randomElement($moroccanTransactionTypes),
            'sign' => $this->faker->boolean,
        ];
    }
}
