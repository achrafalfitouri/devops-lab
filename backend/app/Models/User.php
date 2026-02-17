<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, HasUuids;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'password',
        'photo',
        'first_name',
        'last_name',
        'full_name',
        'cin',
        'phone',
        'email',
        'birthdate',
        'status',
        'code',
        'gender',
        'title_id',
        'password_request_reset',
        'number'

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birthdate' => 'datetime',
    ];

    /**
     * The attributes that should be cast as dates.
     *
     * @var array<int, string>
     */
    protected $dates = ['deleted_at'];

    public function clientLogs()
    {
        return $this->hasMany(ClientLog::class, 'user_id');
    }
    public function title()
    {
        return $this->belongsTo(Titles::class);
    }
    public function role()
    {
        return $this->belongsToMany(Role::class);
    }
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_users', 'user_id', 'role_id');
    }

    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }


    public function assignRole($role)
    {
        $role = Role::where('name', $role)->firstOrFail();
        $this->roles()->attach($role);
    }

    public function revokeRole($role)
    {
        $role = Role::where('name', $role)->firstOrFail();
        $this->roles()->detach($role);
    }

   public function hasPermission($permission)
   {
       $permissions = json_decode($this->permissions, true);

       if (is_array($permissions) && in_array($permission, $permissions)) {
           return true;
       }

       foreach ($this->roles as $role) {
           if ($role->permissions()->where('name', $permission)->exists()) {
               return true;
           }
       }

       return false;
   }

   public function cashRegisters()
   {
       return $this->belongsToMany(CashRegister::class, 'user_cash_registers');
   }
public function assignCashRegister($cr)
    {
        $cr = CashRegister::where('name', $cr)->firstOrFail();
        $this->cashRegisters()->attach($cr);
    }

    public function revokeCashRegister($cr)
    {
        $cr = CashRegister::where('name', $cr)->firstOrFail();
        $this->cashRegisters()->detach($cr);
    }
    public function cashuser()
   {
       return $this->belongsToMany(User::class, 'user_cash_registers','user_id','cash_register_id');
   }

    public function userCash()
    {
        return $this->belongsToMany(UserCashRegister::class,  'user_id');
    }
}
