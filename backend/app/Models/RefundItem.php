<?php
namespace App\Models;

use App\Helpers\UuidHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsItemChanges;


class RefundItem extends Model
{
    use HasFactory, SoftDeletes, UuidHelper,LogsItemChanges;
    protected $table = 'refund_note_items';

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
        'refund_note_id',
        'quote_id'
    ];

    public function productionNote()
    {
        return $this->belongsTo(ProductionNote::class);
    }

    public function refound()
    {
        return $this->belongsTo(RefundNote::class);
    }

}
