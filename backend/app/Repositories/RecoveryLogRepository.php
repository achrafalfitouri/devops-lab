<?php

namespace App\Repositories;

use App\Models\RecoveryLog;
use App\Repositories\Contracts\RecoveryLogRepositoryInterface;
use Illuminate\Support\Facades\Log;

class RecoveryLogRepository implements RecoveryLogRepositoryInterface
{
    protected $model;

    public function __construct(RecoveryLog $recoveryLog)
    {
        $this->model = $recoveryLog;
    }
    public function createLog($action, $oldValue, $newValue, $userId , $entityid)
    {
        try {
            return $this->model->create([
                'action'    => $action,
                'old_value' => json_encode($oldValue),
                'new_value' => json_encode($newValue),  
                'user_id'   => $userId,
                'entity_id' => $entityid,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create log: ' . $e->getMessage());
            throw $e;
        }
    }
    public function getLogs()
    {
        return $this->model->query();
    }
}
