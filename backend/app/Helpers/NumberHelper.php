<?php

namespace App\Helpers;

use NumberFormatter;

class NumberHelper
{
    public static function convertNumberToWords($number)
    {
        try {
            if (class_exists('NumberFormatter')) {
                $number = round($number, 2);
                
                if ($number == floor($number)) {
                    $number = (int) $number;
                }
                
                $formatter = new NumberFormatter('fr', NumberFormatter::SPELLOUT);
                return $formatter->format($number);
            }
        } catch (\Exception $e) {
            return 'Error converting number to words';
        }
        
        return 'NumberFormatter not available';
    }
}