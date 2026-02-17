<?php
namespace App\Models;

use App\Helpers\UuidHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsItemChanges;


class QuoteRequestItem extends Model
{
    use HasFactory, SoftDeletes, UuidHelper,LogsItemChanges;
    protected $fillable = [
        'characteristics','description', 'price', 'quantity', 'undiscounted_amount',
        'discount', 'amount', 'order', 'status', 'quote_request_id'
    ];

    public function quoterequest()
    {
        return $this->belongsTo(QuoteRequest::class);
    }
}
