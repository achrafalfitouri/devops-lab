<?php

namespace Database\Factories;

use App\Helpers\FactoryHelpers;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition()
    {
        $moroccanTitles = ['Responsable Commercial', 'Directeur Financier', 'Chef de Projet', 'Chargé de Clientèle', 'Analyste'];

        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'full_name' => function (array $attributes) {
                return $attributes['first_name'] . ' ' . $attributes['last_name'];
            },
            'title' => $this->faker->randomElement($moroccanTitles),
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'client_id' => FactoryHelpers::getRandomId(\App\Models\Client::class),
        ];
    }
}
