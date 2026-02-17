<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\ClientType;
use App\Models\Gamutes;
use App\Models\Status;
use App\Models\BusinessSector;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = \App\Models\Client::class;

    public function definition()
    {
        $getRandomId = function ($modelClass) {
            return $modelClass::inRandomOrder()->value('id');
        };

        $moroccanCompanies = [
            'Société Maroc Télécom',
            'Cosumar',
            'LabelVie',
            'Attijariwafa Bank',
            'Royal Air Maroc',
            'BMCE Bank of Africa',
            'Banque Populaire',
            'Inwi',
            'Orange Maroc',
            'Afriquia',
            'Total Maroc',
            'Marjane',
            'Aswak Assalam',
            'Managem',
            'ONA Group',
            'Holmarcom',
            'Société Générale Maroc',
            'Crédit Agricole du Maroc',
            'CIH Bank',
            'Lydec',
            'RATP Dev Casablanca',
            'Marsa Maroc',
            'Renault Maroc',
            'Sopriam (Peugeot, Citroën)',
            'Maghreb Steel',
            'Cosmos Electro',
            'BIM Stores Maroc',
            'Centrale Danone Maroc',
            'Dislog Group'
        ];

        return [
            'logo' => $this->faker->imageUrl(),
            'legal_name' => $this->faker->randomElement($moroccanCompanies),
            'balance' => $this->faker->randomFloat(2, 100, 5000),
            'trade_name' => $this->faker->companySuffix,
            'phone_number' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'city_id' => $getRandomId(City::class),
            'address' => $this->faker->address,
            'ice' => strtoupper($this->faker->bothify('??######')), 
            'if' => strtoupper($this->faker->bothify('??######')),
            'tp' => strtoupper($this->faker->bothify('??######')), 
            'client_type_id' => $getRandomId(ClientType::class),
            'gamut_id' => $getRandomId(Gamutes::class),
            'status_id' => $getRandomId(Status::class),
            'business_sector_id' => $getRandomId(BusinessSector::class),
        ];
    }

   
    public function clientDePassage()
    {
        return $this->state(function (array $attributes) {
            $getRandomId = function ($modelClass) {
                return $modelClass::inRandomOrder()->value('id');
            };

            return [
                'legal_name' => 'Client de passage',
                'balance' => null, 
                'trade_name' => 'Client de passage',
                'phone_number' => null, 
                'email' => 'clientdepassage@example.com', 
                'city_id' => $getRandomId(City::class),
                'address' => 'N/A',
                'ice' => 'CLIENT_PASS', 
                'if' => 'CLIENT_PASS',
                'tp' => 'CLIENT_PASS',
              
            ];
        });
    }
    
    
    public function print360Client()
    {
        return $this->state(function (array $attributes) {
            $getMarrakechCityId = function() {
                return City::where('name', 'Marrakech')->first()->id ?? 
                       City::inRandomOrder()->first()->id;
            };
            
            $getRandomId = function ($modelClass) {
                return $modelClass::inRandomOrder()->value('id');
            };
            
            return [
                'legal_name' => '360print',
                'balance' => 0, 
                'trade_name' => '360print',
                'phone_number' => '08 08 68 03 80 - 06 70 03 60 40',
                'email' => 'contact@360print.ma',
                'city_id' => $getMarrakechCityId(),
                'address' => 'IMMEUBLE N°5, RUE CAPITAINE AUDIBERE CAMP EL GHOUL GUELIZ MARRAKECH.',
                'ice' => '003206806000087',
                'if' => '53579478',
                'tp' => '64200842',
                'client_type_id' => $getRandomId(ClientType::class),
                'gamut_id' => $getRandomId(Gamutes::class),
                'status_id' => $getRandomId(Status::class),
                'business_sector_id' => $getRandomId(BusinessSector::class),
                // Note: 'rc' and 'website' fields were removed as they don't exist in the database
                // RC value was: 132591
                // Website value was: www.360print.ma
            ];
        });
    }
}