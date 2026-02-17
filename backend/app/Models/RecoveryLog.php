<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoveryLog extends Model
{
    use SoftDeletes, HasUuids;

    protected $table = 'recovery_logs';

    protected $fillable = [
        'action',
        'old_value',
        'new_value',
        'user_id',
        'entity_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recovery()
    {
        return $this->belongsTo(Recovery::class, 'entity_id');
    }
}
