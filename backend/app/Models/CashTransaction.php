<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    use HasFactory, SoftDeletes, HasUuids;
    protected $table = 'cash_transactions';

    protected $fillable = [
        'cash_register_id',
        'cash_transaction_type_id',
        'amount',
        'code',
        'name',
        'comment',
        'date',
        'user_id',
        'client_id',
        'target_user_id',
        'target_cash_register_id',
        'balance_reset',
        'refund_note_id',
        'seller',
        'bank',
    ];


    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    public function targetCashRegister()
    {
        return $this->belongsTo(CashRegister::class, 'target_cash_register_id');
    }

    public function transactionType()
    {
        return $this->belongsTo(CashTransactionType::class, 'cash_transaction_type_id');
    }
    public function logs()
    {
        return $this->hasMany(CashTransactionLogs::class, 'entity_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }
    public function refundNote()
    {
        return $this->hasMany(CashTransactionLogs::class, 'refund_note_id');
    }
}
