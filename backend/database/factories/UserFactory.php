<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\Titles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $getRandomId = function ($modelClass) {
            $record = $modelClass::inRandomOrder()->first();
            return $record ? $record->id : null;
        };
        return [
            'id' => (string) Str::uuid(),
            'photo' => 'uploads/default.jpg',
            'last_name' => $this->faker->randomElement(['Ben Ali', 'El Fassi', 'Ouahbi', 'Bennani', 'Chraibi']),
            'gender' => $this->faker->randomElement(['Homme', 'Femme']),
            'first_name' => $this->faker->randomElement(['Ahmed', 'Sanaa', 'Mohammed', 'Meryem', 'Rachid']),
            'password_request_reset' => true,
            'full_name' => function (array $attributes) {
                return $attributes['last_name'] . ' ' . $attributes['first_name'];
            },
            'cin' => strtoupper($this->faker->unique()->bothify('MA####')),
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->userName . '@gmail.com',
            'birthdate' => $this->faker->date(),
            'status' => $this->faker->boolean,
            'password' => bcrypt('123456'),
            'created_at' => now(),
            'updated_at' => now(),
            'title_id' => $getRandomId(Titles::class),
            'role_id' => $getRandomId(Role::class),
        ];
    }


    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
