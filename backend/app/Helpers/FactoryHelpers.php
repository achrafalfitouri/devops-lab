<?php 
// app/Helpers/FactoryHelpers.php
namespace App\Helpers;

class FactoryHelpers
{
    public static function getRandomId($modelClass)
    {
        $record = $modelClass::inRandomOrder()->first();
        return $record ? $record->id : null; // Return null if no record found
    }
}

