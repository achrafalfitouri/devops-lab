<?php
namespace Database\Factories;

use App\Models\Quote;
use App\Helpers\FactoryHelpers;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition()
    {
        return [
            'validity_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'is_taxable' => $this->faker->boolean,
            'tax_amount' => $this->faker->randomFloat(2, 0, 500),
            'final_amount' => $this->faker->randomFloat(2, 100, 5000),
            'total_phrase' => $this->faker->sentence,
            'quote_comment' => $this->faker->text(200),
            'client_id' => FactoryHelpers::getRandomId(\App\Models\Client::class),
            'user_id' => FactoryHelpers::getRandomId(\App\Models\User::class),
            'status' => 'Brouillon',

        ];
    }
}
