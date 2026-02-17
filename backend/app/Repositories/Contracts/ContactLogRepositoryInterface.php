<?php

namespace App\Repositories\Contracts;

interface ContactLogRepositoryInterface
{
    public function createLog($action, $oldValue, $newValue, $userId, $entityId);
    public function getLogs();
}
