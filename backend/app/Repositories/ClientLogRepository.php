<?php

namespace App\Repositories;

use App\Models\ClientLog;
use App\Repositories\Contracts\ClientLogRepositoryInterface;
use Illuminate\Support\Facades\Log;

class ClientLogRepository implements ClientLogRepositoryInterface
{
    protected $model;

    public function __construct(ClientLog $clientLog)
    {
        $this->model = $clientLog;
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
