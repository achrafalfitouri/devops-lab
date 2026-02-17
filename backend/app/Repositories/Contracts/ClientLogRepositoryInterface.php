<?php

namespace App\Repositories\Contracts;

interface ClientLogRepositoryInterface
{
    public function createLog($action, $oldValue, $newValue, $userId, $entityId);
    public function getLogs();
}
