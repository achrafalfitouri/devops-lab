<?php

namespace App\Models;

use App\Helpers\UuidHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;



class Payment extends Model
{
    use HasFactory, SoftDeletes,UuidHelper;

    protected $fillable = [
        'code', 'date', 'amount', 'comment', 'payment_type_id'
        , 'client_id','recovery_id'
    ];

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class, 'payment_type_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    public function orderReceipt()
    {
        return $this->belongsTo(OrderReceipt::class);
    }

    public function logs()
    {
        return $this->hasMany(PaymentLog::class, 'entity_id');
    }

    public function recovery()
    {
        return $this->belongsTo(Recovery::class, 'recovery_id');
    }

    protected $casts = [
        'date' => 'datetime',
        'check_date' => 'datetime',
        'effect_date' => 'datetime',
    ];
    
   
}
