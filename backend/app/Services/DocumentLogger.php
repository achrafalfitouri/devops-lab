<?php
namespace App\Services;
use Illuminate\Support\Facades\Auth;
use App\Models\DocumentLog;
use App\Models\ItemLog;

class DocumentLogger
{
    public function logUpdate($model, $oldValues = null, $newValues = null)
    {
        if ($oldValues === null) {
            $oldValues = $model->getOriginal();
        }
        if ($newValues === null) {
            $newValues = $model->toArray();
        }
        
        $result = $this->log($model, 'update', $oldValues, $newValues);
        
        return [
            'success' => true,
            'action' => 'update',
            'document_type' => class_basename($model),
            'entity_id' => $model->id,
            'document_log' => $result
        ];
    }

    public function logDelete($model, $oldValues = null)
    {
        if ($oldValues === null) {
            $oldValues = $model->toArray();
        }
        
        $result = $this->log($model, 'delete', $oldValues, null);
        
        return [
            'success' => true,
            'action' => 'delete',
            'document_type' => class_basename($model),
            'entity_id' => $model->id,
            'document_log' => $result
        ];
    }

    public function logCreate($model, $newValues = null)
    {
        if ($newValues === null) {
            $newValues = $model->toArray();
        }
        
        $result = $this->log($model, 'create', null, $newValues);
        
        return [
            'success' => true,
            'action' => 'create',
            'document_type' => class_basename($model),
            'entity_id' => $model->id,
            'document_log' => $result
        ];
    }

    protected function log($model, $action, $oldValues = null, $newValues = null)
    {
        $documentType = class_basename($model);
        
        $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
        $newValuesJson = $newValues ? json_encode($newValues) : null;
        
        return DocumentLog::create([
            'document_type' => $documentType,
            'entity_id' => $model->id,
            'action' => $action,
            'old_value' => $oldValuesJson,
            'new_value' => $newValuesJson,
            'user_id' => Auth::id(),
        ]);
    }

    public function logItemChange($parentModel, $action, $itemOld = null, $itemNew = null)
    {
        $documentLog = $this->log($parentModel, $action, $parentModel->toArray(), $parentModel->toArray());
        
       
        $itemLog = ItemLog::create([
            'document_log_id' => $documentLog->id,
            'old_value' => json_encode($itemOld),
            'new_value' => json_encode($itemNew),
        ]);
        
        return [
            'success' => true,
            'action' => $action,
            'document_type' => class_basename($parentModel),
            'entity_id' => $parentModel->id,
            'document_log' => $documentLog,
            'item_log' => $itemLog
        ];
    }
}