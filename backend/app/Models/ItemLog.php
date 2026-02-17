<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\UuidHelper;
use App\Traits\DocumentLoggable;

class ItemLog extends Model
{
    use HasFactory, SoftDeletes, UuidHelper,DocumentLoggable;

    protected $fillable = [
      'item_type',
        'entity_id',
        'document_id',
        'action',
        'old_value',
        'new_value',
        'user_id',
    ];

    protected $casts = [
        'old_value' => 'array', 
        'new_value' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function documentLog()
    {
        return $this->belongsTo(DocumentLog::class);
    }
    
}