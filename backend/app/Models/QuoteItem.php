<?php
namespace App\Models;

use App\Helpers\UuidHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsItemChanges;


class QuoteItem extends Model
{
    use HasFactory, SoftDeletes, UuidHelper,LogsItemChanges;
    protected $fillable = [
        'description', 'price', 'quantity', 'undiscounted_amount',
        'discount', 'amount', 'order', 'status', 'quote_id'
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
}
