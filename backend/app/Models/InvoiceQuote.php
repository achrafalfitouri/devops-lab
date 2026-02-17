<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class InvoiceQuote extends Model
{
    use HasFactory, SoftDeletes,HasUuids;

    protected $fillable = ['quote_id','invoice_id' ];
    public function invoiceQuote()
    {
        return $this->belongsTo(InvoiceQuote::class , 'id');
    }

}
