<?php
namespace Database\Factories;

use App\Models\OrderNote;
use App\Helpers\FactoryHelpers;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderNoteFactory extends Factory
{
    protected $model = OrderNote::class;

    public function definition()
    {
        return [
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'is_taxable' => $this->faker->boolean,
            'tax_amount' => $this->faker->randomFloat(2, 0, 500),
            'final_amount' => $this->faker->randomFloat(2, 100, 5000),
            'total_phrase' => $this->faker->sentence,
            'order_comment' => $this->faker->text(),
            'status' => 'Brouillon',
            'client_id' => FactoryHelpers::getRandomId(\App\Models\Client::class),
            'user_id' => FactoryHelpers::getRandomId(\App\Models\User::class),
            'quote_id' => FactoryHelpers::getRandomId(\App\Models\Quote::class),
        ];
    }
}
