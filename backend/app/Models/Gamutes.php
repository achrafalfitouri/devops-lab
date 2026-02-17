<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Gamutes extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'name'
    ];

    protected $dates = ['deleted_at']; 

    public function clients()
    {
        return $this->hasMany(Client::class);
    }
}
