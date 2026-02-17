<?php

namespace App\Repositories\Contracts;

interface CashRegisterDailyBalancesRepositoryInterface
{
    public function storeBalance($cashRegisterId, $balance);
    public function updateOrCreateDailyBalance($cashRegisterId, $balance, $inflows ,$outflows);
    public function updateBalance($cashRegisterId, $balance);
}
