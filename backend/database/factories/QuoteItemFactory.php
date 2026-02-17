<?php
namespace Database\Factories;
use App\Helpers\FactoryHelpers;
use App\Models\QuoteItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteItemFactory extends Factory
{
    protected $model = QuoteItem::class;

    public function definition()
    {


        return [
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 10, 500),
            'amount' => $this->faker->randomFloat(2, 50, 5000),
            'undiscounted_amount' => $this->faker->randomFloat(2, 50, 5200),
            'quantity' => $this->faker->numberBetween(1, 100),
            'discount' => $this->faker->randomFloat(2, 0, 50),
            'status' => $this->faker->sentence,

            'quote_id' => FactoryHelpers::getRandomId(\App\Models\Quote::class),
     

        ];
    }
}
