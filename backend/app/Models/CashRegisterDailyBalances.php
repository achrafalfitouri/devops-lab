<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CashRegisterDailyBalances extends Model
{
    use HasFactory, SoftDeletes, HasUuids;
    protected $table = 'cash_register_daily_balances';

    protected $fillable = [ 'cash_register_id','balance','inflows','outflows'];


    protected $dates = ['deleted_at'];

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class , 'cash_register_id');
    }
}
