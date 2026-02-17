<?php

namespace App\Repositories;

use App\Models\PaymentLog;
use App\Repositories\Contracts\PaymentLogRepositoryInterface;
use Illuminate\Support\Facades\Log;

class PaymentLogRepository implements PaymentLogRepositoryInterface
{
    protected $model;

    public function __construct(PaymentLog $paymentLog)
    {
        $this->model = $paymentLog;
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