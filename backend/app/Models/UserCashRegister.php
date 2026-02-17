<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class UserCashRegister extends Model
{
    use HasFactory, SoftDeletes,HasUuids;

    protected $fillable = ['user_id','cash_register_id' ];
    public function usercash()
    {
        return $this->belongsTo(User::class , 'user_id');
    }
    public function usercashregister()
    {
        return $this->belongsTo(CashRegister::class , 'cash_register_id');
    }
    public $incrementing = false;
}
