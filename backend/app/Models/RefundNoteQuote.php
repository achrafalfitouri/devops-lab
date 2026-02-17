<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class RefundNoteQuote extends Model
{
    use HasFactory, SoftDeletes,HasUuids;

    protected $fillable = ['quote_id','refund_note_id' ];
    public function refundNoteQuote()
    {
        return $this->belongsTo(RefundNoteQuote::class , 'id');
    }

}
