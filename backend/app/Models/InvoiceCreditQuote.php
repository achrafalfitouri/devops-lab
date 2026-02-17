<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class InvoiceCreditQuote extends Model
{
    use HasFactory, SoftDeletes,HasUuids;

    protected $fillable = ['quote_id','invoice_credit_id' ];
    public function invoiceCreditQuote()
    {
        return $this->belongsTo(InvoiceCreditQuote::class , 'id');
    }

}
