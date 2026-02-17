<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashTransactionLogs extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'action',
        'old_value',
        'new_value',
        'user_id',
        'entity_id',
    ];

    protected $casts = [
        'old_value' => 'json',
        'new_value' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transaction()
    {
        return $this->belongsTo(CashTransaction::class, 'entity_id');
    }
}
