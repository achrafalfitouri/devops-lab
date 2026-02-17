<?php

namespace App\Services;

use App\Models\ItemLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ItemLogger
{
    public function logCreate($model, $newValues)
    {
        return $this->log($model, 'create', null, $newValues);
    }

    public function logUpdate($model, $oldValues, $newValues)
    {
        return $this->log($model, 'update', $oldValues, $newValues);
    }
    
    public function logDelete($model, $oldValues)
    {
        return $this->log($model, 'delete', $oldValues, null);
    }

    protected function log($model, $action, $oldValues = null, $newValues = null)
    {
        $itemType = class_basename($model);
        
        $oldValuesJson = is_array($oldValues) ? json_encode($oldValues) : $oldValues;
        $newValuesJson = is_array($newValues) ? json_encode($newValues) : $newValues;
        
        return ItemLog::create([
            'item_type' => $itemType,
            'entity_id' => $model->id,
            'document_id' => $model->quote_id, 
            'action' => $action,
            'old_value' => $oldValuesJson,
            'new_value' => $newValuesJson,
            'user_id' => Auth::id(),
        ]);
    }
}