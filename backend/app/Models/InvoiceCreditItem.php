<?php
namespace App\Models;

use App\Helpers\UuidHelper;
use App\Traits\LogsItemChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceCreditItem extends Model
{
    use HasFactory, SoftDeletes, UuidHelper,LogsItemChanges;
    protected $fillable = [
        'description', 'price', 'quantity', 'undiscounted_amount',
        'discount', 'amount', 'order', 'status', 'production_note_id', 'invoice_credit_id','quote_id'
    ];

    public function invoiceCredit()
    {
        return $this->belongsTo(InvoiceCredit::class);
    }

    public function productionNote()
    {
        return $this->belongsTo(ProductionNote::class);
    }
    public function refunds()
    {
        return $this->hasMany(RefundNote::class);
    }
}
