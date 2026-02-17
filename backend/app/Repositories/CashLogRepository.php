<?php

namespace App\Repositories;

use App\Models\CashTransactionLogs;
use App\Repositories\Contracts\CashLogRepositoryInterface;
use Illuminate\Support\Facades\Log;

class CashLogRepository implements CashLogRepositoryInterface
{
    protected $model;

    public function __construct(CashTransactionLogs $CashTransactionLogs)
    {
        $this->model = $CashTransactionLogs;
    }

    public function createLog($action, $oldValue, $newValue, $userId, $entityId)
    {
        try {
            return $this->model->create([
                'action'    => $action,
                'old_value' => json_encode($oldValue),
                'new_value' => json_encode($newValue),  
                'user_id'   => $userId,
                'entity_id' => $entityId,
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
