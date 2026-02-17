<?php

namespace App\Repositories\Contracts;

interface PaymentLogRepositoryInterface
{
   
    public function createLog($action, $oldValue, $newValue, $userId ,$entityid);
    public function getLogs();
}