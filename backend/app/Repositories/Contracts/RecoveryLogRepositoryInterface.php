<?php

namespace App\Repositories\Contracts;

interface RecoveryLogRepositoryInterface
{
   
    public function createLog($action, $oldValue, $newValue, $userId ,$entityid);
    public function getLogs();
}
