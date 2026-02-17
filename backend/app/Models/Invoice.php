<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\UuidHelper;
use App\Traits\DocumentLoggable;
use App\Traits\ItemLoggable;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, UuidHelper, DocumentLoggable;

    protected $fillable = [
        'due_date',
        'amount',
        'discount',
        'discounted_amount',
        'total_phrase',
        'is_taxable',
        'tax_amount',
        'final_amount',
        'invoice_comment',
        'status',
        'client_id',
        'user_id',
        'code',
        'quote_id',
        'payed_amount',
        'total_to_pay',
        'process_group_id'

    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderUser()
    {
        return $this->belongsTo(User::class, 'order_user_id');
    }

    public function quotes()
    {
        return $this->belongsTo(Quote::class, 'quote_id', 'id');
    }

    public function quoterequests()
    {
        return $this->belongsTo(QuoteRequest::class, 'quote_id', 'quote_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function orderNotes()
    {
        return $this->hasMany(OrderNote::class, 'quote_id', 'quote_id');
    }

    public function outputNotes()
    {
        return $this->hasMany(OutputNote::class, 'quote_id', 'quote_id');
    }

    public function deliveryNotes()
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

    public function invoiceCredits()
    {
        return $this->hasMany(InvoiceCredit::class, 'quote_id', 'quote_id');
    }
    public function productionNotes()
    {
        return $this->hasMany(ProductionNote::class, 'quote_id', 'quote_id');
    }

    public function invoices()
    {

            return $this->hasMany(Invoice::class, 'quote_id', 'quote_id');

    }
    public function refunds()
    {
        return $this->hasOne(RefundNote::class, 'quote_id', 'quote_id');
    }
    //pivot
    public function invoiceQuotes()
    {
        return $this->hasMany(InvoiceQuote::class, 'invoice_id', 'id');
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

       //new function to get payments
       public function payments()
       {
           return $this->belongsToMany(Payment::class, 'payment_documents', 'invoice_id', 'payment_id')
               ->withTimestamps()
               ->withPivot('order_receipt_id');
       }
        
}
