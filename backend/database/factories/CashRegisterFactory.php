<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CashRegisterFactory extends Factory
{
    private static $number = 3200;

    public function definition(): array
    {
        $yearSuffix = now()->format('y');

        $code = 'CA-' . $yearSuffix . '-' . self::$number++;

        $moroccanNames = ['Marjane', 'Aswak Assalam', 'Label\'Vie', 'Carrefour Market', 'Kitea', 'Bim', 'Sopriam'];

        return [
            'id' => Str::uuid(),
            'name' => $this->faker->randomElement($moroccanNames),
            'code' => $code,
            'balance' => $this->faker->numberBetween(1000, 10000),
        ];
    }
}
