<?php
namespace Database\Factories;

use App\Models\OutputNote;
use App\Helpers\FactoryHelpers;
use Illuminate\Database\Eloquent\Factories\Factory;

class OutputNoteFactory extends Factory
{
    protected $model = OutputNote::class;

    public function definition()
    {
        return [
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'is_taxable' => $this->faker->boolean,
            'tax_amount' => $this->faker->randomFloat(2, 0, 500),
            'status' => 'Brouillon',
            'final_amount' => $this->faker->randomFloat(2, 100, 5000),
            'total_phrase' => $this->faker->sentence,
            'output_comment' => $this->faker->text(200),
            'output_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'production_note_id' => FactoryHelpers::getRandomId(\App\Models\ProductionNote::class),
            'client_id' => FactoryHelpers::getRandomId(\App\Models\Client::class),
            'user_id' => FactoryHelpers::getRandomId(\App\Models\User::class),
            'quote_id' => FactoryHelpers::getRandomId(\App\Models\Quote::class),

        ];
    }
}
