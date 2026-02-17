<?php
namespace App\Models;

use App\Helpers\UuidHelper;
use App\Traits\LogsItemChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderNoteItem extends Model
{
    use HasFactory, SoftDeletes, UuidHelper,LogsItemChanges;
    protected $fillable = [
        'description', 'price', 'quantity', 'undiscounted_amount',
        'discount', 'amount', 'order', 'status', 'order_note_id'
    ];

    public function orderNote()
    {
        return $this->belongsTo(OrderNote::class );
    }
}
