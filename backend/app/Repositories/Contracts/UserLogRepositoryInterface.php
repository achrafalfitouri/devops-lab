<?php

namespace App\Repositories\Contracts;

interface UserLogRepositoryInterface
{
    public function createLog($action, $oldValue, $newValue, $userId ,$entityid);
    public function getLogs();
}

