<?php
namespace App\Models;

use App\Helpers\UuidHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes,UuidHelper;

    protected $fillable = [
        'code', 'first_name', 'last_name', 'full_name', 'title', 'phone', 'email', 'client_id'
    ];

    

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function logs()
    {
        return $this->hasMany(ContactLog::class, 'entity_id');
    }
}
