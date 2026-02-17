<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmailTemplate extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    
    protected $fillable = [
        'id',
        'name','subject','content'
    ];

    protected $dates = ['deleted_at'];
    public function documentTypes()
    {
        return $this->belongsToMany(EmailDocumentType::class, 'email_template_document_types', 'email_templates_id', 'email_document_types_id');
    }
   
}
