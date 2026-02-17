<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplateDocumentType extends Model
{
    use HasFactory, SoftDeletes,HasUuids;

    protected $fillable = ['email_templates_id', 'email_document_types_id'];

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_templates_id');
    }

    public function documentType()
    {
        return $this->belongsTo(EmailDocumentType::class, 'email_document_types_id');
    }
}
