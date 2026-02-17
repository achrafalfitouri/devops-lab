<?php

namespace Database\Factories;

use App\Models\CashRegister;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CashRegisterDailyBalancesFactory extends Factory
{

    public function definition(): array
    {
      
 
        $getRandomId = function ($modelClass) {
            $record = $modelClass::inRandomOrder()->first();
            return $record ? $record->id : null; 
        };
    {
        return [
            'id' => Str::uuid(),
            'balance'=> $this->faker->numberBetween(1000, 10000),
            'outflows' =>$this->faker->numberBetween(100, 1000),
            'inflows' => $this->faker->numberBetween(100, 1000),
            'cash_register_id' => $getRandomId(CashRegister::class),
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'), 

        ];
    }
}
}