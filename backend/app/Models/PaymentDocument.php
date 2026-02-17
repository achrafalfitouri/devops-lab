<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PaymentDocument extends Model
{
    use HasFactory, SoftDeletes,HasUuids;

    protected $fillable = ['order_receipt_id','invoice_id','payment_id' ];
  

}
