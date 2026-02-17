<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\UuidHelper;

class DocumentLog extends Model
{
    use HasFactory, SoftDeletes, UuidHelper;

    protected $fillable = [
        'document_type',
        'entity_id',
        'action',
        'old_value',
        'new_value',
        'user_id'
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ItemLog()
    {
        return $this->belongsTo(ItemLog::class);
    }
}
