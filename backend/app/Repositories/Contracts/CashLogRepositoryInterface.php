<?php

namespace App\Repositories\Contracts;

interface CashLogRepositoryInterface
{
    public function createLog($action, $oldValue, $newValue, $userId, $entityId);
    public function getLogs();
}
