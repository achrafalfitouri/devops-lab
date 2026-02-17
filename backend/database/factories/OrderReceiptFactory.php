<?php

namespace Database\Factories;

use App\Helpers\FactoryHelpers;
use App\Models\OrderReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderReceiptFactory extends Factory
{
    protected $model = OrderReceipt::class;

    public function definition()
    {
        return [
            'code' => $this->faker->unique()->numerify('OR####'),
            'due_date' => $this->faker->date(),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'discount' => $this->faker->randomFloat(2, 0, 500),
            'discounted_amount' => $this->faker->randomFloat(2, 50, 9500),
            'is_taxable' => false,
            'tax_amount' => 0,
            'final_amount' => $this->faker->randomFloat(2, 100, 10000),
            'total_phrase' => $this->faker->sentence(),
            'receipt_comment' => $this->faker->text(),
            'status' => 'Brouillon',
            'client_id' => \App\Models\Client::factory(),
            'user_id' => \App\Models\User::factory(),
            'quote_id' => FactoryHelpers::getRandomId(\App\Models\Quote::class),

        ];
    }
}

