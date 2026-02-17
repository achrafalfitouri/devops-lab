<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ReturnNoteQuote extends Model
{
    use HasFactory, SoftDeletes,HasUuids;

    protected $fillable = ['quote_id','return_note_id' ];
    public function returnNoteQuote()
    {
        return $this->belongsTo(ReturnNoteQuote::class , 'id');
    }

}
