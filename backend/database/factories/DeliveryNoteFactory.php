<?php

namespace Database\Factories;

use App\Models\DeliveryNote;
use App\Helpers\FactoryHelpers;
use Illuminate\Database\Eloquent\Factories\Factory;


class DeliveryNoteFactory extends Factory
{
    protected $model = DeliveryNote::class;

    public function definition()
    {
        return [
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'is_taxable' => $this->faker->boolean,
            'tax_amount' => $this->faker->randomFloat(2, 0, 200),
            'final_amount' => $this->faker->randomFloat(2, 100, 12000),
            'total_phrase' => $this->faker->sentence,
            'delivery_comment' => $this->faker->sentence,
            'status' => 'Brouillon',
            'output_note_id' => FactoryHelpers::getRandomId(\App\Models\OutputNote::class),
            'client_id' => FactoryHelpers::getRandomId(\App\Models\Client::class),
            'user_id' => FactoryHelpers::getRandomId(\App\Models\User::class),
            'quote_id' => FactoryHelpers::getRandomId(\App\Models\Quote::class),

        ];
    }
}
