<?php

namespace Database\Factories;

use App\Models\UserCashRegister;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\CashRegister;

class UserCashRegisterFactory extends Factory
{
    protected $model = UserCashRegister::class;

    public function definition(): array
    {
        $getRandomId = function ($modelClass) {
            $record = $modelClass::inRandomOrder()->first();
            return $record ? $record->id : null; 
        };
        return [
            'id' => Str::uuid(),
            'user_id'  => $getRandomId(User::class),
            'cash_register_id' => $getRandomId(CashRegister::class),

        ];
    }
}
