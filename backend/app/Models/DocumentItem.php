<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\UuidHelper;
use App\Traits\LogsItemChanges;

class DocumentItem extends Model
{
    use HasFactory, SoftDeletes, UuidHelper,LogsItemChanges;
    protected $fillable = [
        'description',
        'price',
        'amount',
        'undiscounted_amount',
        'quantity',
        'production_note_id',
        'discount',
        'output_note_id',
        'return_note_id',
        'invoice_id',
        'quote_id',
        'delivery_note_id',
        'order_note_id',
        'order_receipt_id',
        'invoice_credit_id',
        'order'
    ];

    public function productionNote()
    {
        return $this->belongsTo(ProductionNote::class);
    }

    public function outputNote()
    {
        return $this->belongsTo(OutputNote::class);
    }

    public function returnNote()
    {
        return $this->belongsTo(ReturnNote::class);
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
    public function orderReciept()
    {
        return $this->belongsTo(OrderReceipt::class, 'order_receipt_id');
    }

    public function orderNote()
    {
        return $this->belongsTo(OrderNote::class, 'order_note_id');
    }
    public function invoiceCredits()
    {
        return $this->belongsTo(InvoiceCredit::class, 'invoice_credit_id');
    }

}
