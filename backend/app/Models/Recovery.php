<?php

namespace App\Models;

use App\Helpers\UuidHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;



class Recovery extends Model
{
    use HasFactory, SoftDeletes,UuidHelper;

    protected $fillable = [
        'code', 'date', 'amount', 'comment', 'payment_type_id', 'recovery_balance',
        'check_number', 'wire_transfer_number',
         'effect_number', 'client_id'
    ];

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class, 'payment_type_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'recovery_id');
    }
 
    public function logs()
    {
        return $this->hasMany(PaymentLog::class, 'entity_id');
    }

    protected $casts = [
        'date' => 'datetime',
        'check_date' => 'datetime',
        'effect_date' => 'datetime',
    ];
    
   
}
