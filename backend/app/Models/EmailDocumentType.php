<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailDocumentType extends Model
{
    use HasFactory, SoftDeletes,HasUlids;

    protected $fillable = ['name'];

    public function emailTemplates()
    {
        return $this->belongsToMany(EmailTemplate::class, 'email_template_document_types', 'email_document_types_id', 'email_templates_id');
    }
}
