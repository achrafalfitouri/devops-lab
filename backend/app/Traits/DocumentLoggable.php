<?php
namespace App\Traits;

use App\Services\DocumentLogger;
use Illuminate\Database\Eloquent\Model;

trait DocumentLoggable
{
    protected static function bootDocumentLoggable()
    {
        
        static::updated(function (Model $model) {
            $logger = new DocumentLogger();
            $changes = $model->getDirty();
            unset($changes['updated_at']);
            if (empty($changes)) {
                return;
            }            
            $oldValues = $model->getOriginal();
            $newValues = array_merge($oldValues, $changes);
            $logger->logUpdate($model, $oldValues, $newValues);


            if (!empty($newValues)) {
                $logger->logUpdate($model, $oldValues, $newValues);
            }
        });

        static::deleted(function (Model $model) {
            $logger = new DocumentLogger();
            $logger->logDelete($model, $model->toArray());
        });
    }
}