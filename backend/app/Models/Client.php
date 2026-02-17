<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Client extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'logo',
        'legal_name',
        'trade_name',
        'phone_number',
        'email',
        'city_id',
        'address',
        'ice',
        'if',
        'tp',
        'rc',
        'client_type_id',
        'gamut_id',
        'status_id',
        'business_sector_id',
        'code',
        'balance'
    ];


    protected $dates = ['deleted_at']; 
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function clientType()
    {
        return $this->belongsTo(ClientType::class);
    }

    public function gamut()
    {
        return $this->belongsTo(Gamutes::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function businessSector()
    {
        return $this->belongsTo(BusinessSector::class , 'business_sector_id');
    }
    public function logs()
    {
        return $this->hasMany(ClientLog::class, 'entity_id');
    }
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'client_id');
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'client_id');
    }
}
