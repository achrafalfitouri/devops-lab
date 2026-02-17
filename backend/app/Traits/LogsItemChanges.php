<?php
namespace App\Traits;

use App\Services\ItemLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

trait LogsItemChanges
{
    protected static function bootLogsItemChanges()
    {
        static::updating(function (Model $model) {
            $logger = new ItemLogger();

            $changes = $model->getDirty();
            Log::debug('Model dirty attributes: ' . json_encode($changes));
            
            unset($changes['updated_at']);
            if (empty($changes)) {
                Log::debug('No changes to log after removing updated_at');
                return;
            }

            $oldValues = $model->getOriginal();
            $newValues = array_merge($oldValues, $changes);

            Log::debug('Logging update: ' . json_encode([
                'old' => $oldValues,
                'new' => $newValues
            ]));
            
            $logger->logUpdate($model, $oldValues, $newValues);
        });

        static::deleted(function (Model $model) {
            $logger = new ItemLogger();
            $logger->logDelete($model, $model->toArray());
        });
    }
}