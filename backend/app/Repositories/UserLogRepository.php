<?php

namespace App\Repositories;

use App\Models\UserLog;
use App\Repositories\Contracts\UserLogRepositoryInterface;
use Illuminate\Support\Facades\Log;

class UserLogRepository implements UserLogRepositoryInterface
{
    protected $model;

    public function __construct(UserLog $userLog)
    {
        $this->model = $userLog;
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
