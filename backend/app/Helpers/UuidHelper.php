<?php

// app/Helpers/UuidHelper.php
namespace App\Helpers;

use Illuminate\Support\Str;

trait UuidHelper
{
    /**
     * Boot function from Laravel to automatically generate UUIDs.
     */
    protected static function bootUuidHelper()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid(); 
            }
        });
    }

   
    public function getKeyType()
    {
        return 'string';
    }

    /**
     * Disable auto-incrementing as UUIDs are not numeric.
     */
    public function getIncrementing()
    {
        return false;
    }
}
