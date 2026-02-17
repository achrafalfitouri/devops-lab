<?php
namespace App\Models;

use App\Helpers\UuidHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactLog extends Model
{
    use HasFactory, SoftDeletes,UuidHelper;

    protected $fillable = [
        'action', 'old_value', 'new_value', 'user_id', 'entity_id'
    ];

    protected $keyType = 'uuid';
    public $incrementing = false;

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'entity_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
