<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\CashRegisterDailyBalancesRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CashRegisterDailyBalancesController extends Controller
{
    protected $repository;

    public function __construct(CashRegisterDailyBalancesRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function storeDailyBalance($cashRegisterId, $balance)
    {
        $this->repository->storeBalance($cashRegisterId, $balance);
    }

    public function updateDailyBalance($cashRegisterId, $balance)
    {
        $this->repository->updateBalance($cashRegisterId, $balance);
    }
}
