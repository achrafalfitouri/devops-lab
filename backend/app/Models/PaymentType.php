<?php

namespace App\Models;

use App\Helpers\UuidHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentType extends Model
{
    use HasFactory, SoftDeletes,UuidHelper;

    protected $fillable = ['name'];

    public function payments()
    {
        return $this->hasMany(Payment::class, 'payment_type_id');
    }
}
