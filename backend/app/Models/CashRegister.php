<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = ['name', 'balance', 'code', 'status'];

    public function transactions()
    {
        return $this->hasMany(CashTransaction::class, 'cash_register_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserCashRegister::class);
    }
    public function cashRegisterType()
    {
        return $this->belongsTo(CashTransactionType::class, 'cash_transaction_type_id');
    }

    public function DailyBalances()
    {
        return $this->hasMany(CashRegisterDailyBalances::class, 'cash_register_id');
    }
    public function usercash()
    {
        return $this->hasMany(UserCashRegister::class, 'cash_register_id');
    }
    public function cashuser()
    {
        return $this->belongsToMany(User::class, 'user_cash_registers', 'user_id', 'cash_register_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'created_at' => 'datetime:Y/m/d',
        'updated_at' => 'datetime:Y/m/d',
    ];

    public function managed_by()
    {
        return $this->belongsToMany(User::class, 'user_cash_registers', 'cash_register_id', 'user_id')
            ->wherePivotNull('deleted_at');
    }
}
