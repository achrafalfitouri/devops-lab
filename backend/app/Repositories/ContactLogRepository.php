<?php

namespace App\Repositories;

use App\Models\ContactLog;
use App\Repositories\Contracts\ContactLogRepositoryInterface;
use Illuminate\Support\Facades\Log;

class ContactLogRepository implements ContactLogRepositoryInterface
{
    protected $model;

    public function __construct(ContactLog $ContactLog)
    {
        $this->model = $ContactLog;
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
