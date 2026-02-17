<?php
namespace Database\Factories;

use App\Models\InvoiceCredit;
use App\Helpers\FactoryHelpers;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceCreditFactory extends Factory
{
    protected $model = InvoiceCredit::class;

    public function definition()
    {
        return [
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'discount' => $this->faker->randomFloat(2, 0, 500),
            'discounted_amount' => $this->faker->randomFloat(2, 100, 4500),
            'is_taxable' => $this->faker->boolean,
            'tax_amount' => $this->faker->randomFloat(2, 0, 500),
            'final_amount' => $this->faker->randomFloat(2, 100, 5000),
            'total_phrase' => $this->faker->sentence,
            'credit_comment' => $this->faker->text(200),
            'status' => 'Brouillon',
            'client_id' => FactoryHelpers::getRandomId(\App\Models\Client::class),
            'user_id' => FactoryHelpers::getRandomId(\App\Models\User::class),
            'quote_id' => FactoryHelpers::getRandomId(\App\Models\Quote::class),
        ];
        }
    }