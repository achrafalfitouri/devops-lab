<?php
namespace App\Models;

use App\Helpers\UuidHelper;
use App\Traits\LogsItemChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderReceiptItem extends Model
{
    use HasFactory, SoftDeletes, UuidHelper,LogsItemChanges;
    protected $fillable = [
        'description',
        'price',
        'quantity',
        'undiscounted_amount',
        'discount',
        'amount',
        'order',
        'status',
        'production_note_id',
        'order_receipt_id',
        'quote_id'


    ];

    public function orderReceipt()
    {
        return $this->belongsTo(OrderReceipt::class);
    }

    public function productionNote()
    {
        return $this->belongsTo(ProductionNote::class);
    }
}
