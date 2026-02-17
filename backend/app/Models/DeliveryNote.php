<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\UuidHelper;
use App\Traits\DocumentLoggable;

class DeliveryNote extends Model
{
    use HasFactory, SoftDeletes, UuidHelper, DocumentLoggable;

    protected $fillable = [
        'amount',
        'is_taxable',
        'tax_amount',
        'final_amount',
        'total_phrase',
        'delivery_comment',
        'status',
        'output_note_id',
        'client_id',
        'user_id',
        'code',
        'quote_id',
        'order_receipt_id',
        'invoice_id',
        'return_note_id',
        'process_group_id'

    ];

    // In DeliveryNote model - keep it simple
    public function refunds()
    {
        return $this->hasMany(RefundNote::class, 'quote_id', 'quote_id');
    }


    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }




    public function findByDeliveryNoteId($deliveryNoteId)
    {
        return DocumentItem::where('delivery_note_id', $deliveryNoteId)->get();
    }

    public function quotes()
    {
        return $this->belongsTo(Quote::class, 'quote_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }

    public function orderNotes()
    {
        return $this->hasOne(OrderNote::class, 'quote_id', 'quote_id');
    }

    public function outputNotes()
    {
        return $this->hasOne(OutputNote::class, 'quote_id', 'quote_id');
    }

    public function productionNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'quote_id', 'quote_id');
    }

    public function returnNotes()
    {


        return $this->hasMany(ReturnNote::class, 'quote_id', 'quote_id');
    }

    public function orderReceipts()
    {

        return $this->hasMany(OrderReceipt::class, 'quote_id', 'quote_id');
    }

    public function invoices()
    {
        if ($this->process_group_id) {
            return $this->hasMany(Invoice::class, 'process_group_id', 'process_group_id');
        } else {
            return $this->hasMany(Invoice::class, 'quote_id', 'quote_id');
        }
    }



    public function invoiceCredits()
    {

        return $this->hasMany(InvoiceCredit::class, 'quote_id', 'quote_id');
    }
    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'quote_id', 'quote_id');
    }

    public function quoterequests()
    {
        return $this->belongsTo(QuoteRequest::class, 'quote_id', 'quote_id');
    }

       // =================== DUAL RELATIONSHIPS ===================

       public function invoicesByProcess()
       {
           return $this->hasMany(Invoice::class, 'process_group_id', 'process_group_id');
       }

       public function invoicesByQuote()
       {
           return $this->hasMany(Invoice::class, 'quote_id', 'quote_id');
       }

       public function orderReceiptsByProcess()
       {
           return $this->hasMany(OrderReceipt::class, 'process_group_id', 'process_group_id');
       }

       public function orderReceiptsByQuote()
       {
           return $this->hasMany(OrderReceipt::class, 'quote_id', 'quote_id');
       }

       public function returnNotesByProcess()
       {
           return $this->hasMany(ReturnNote::class, 'process_group_id', 'process_group_id');
       }

       public function returnNotesByQuote()
       {
           return $this->hasMany(ReturnNote::class, 'quote_id', 'quote_id');
       }

       public function invoiceCreditsByProcess()
       {
           return $this->hasMany(InvoiceCredit::class, 'process_group_id', 'process_group_id');
       }

       public function invoiceCreditsByQuote()
       {
           return $this->hasMany(InvoiceCredit::class, 'quote_id', 'quote_id');
       }

       public function refundsByProcess()
       {
           return $this->hasMany(RefundNote::class, 'process_group_id', 'process_group_id');
       }

       public function refundsByQuote()
       {
           return $this->hasMany(RefundNote::class, 'quote_id', 'quote_id');
       }
}
