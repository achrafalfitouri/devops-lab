<?php

namespace App\Repositories;

use App\Models\CashRegisterDailyBalances;
use App\Repositories\Contracts\CashRegisterDailyBalancesRepositoryInterface;
use Carbon\Carbon;

class CashRegisterDailyBalancesRepository implements CashRegisterDailyBalancesRepositoryInterface
{
    protected $cashRegisterDailyBalances;

    public function __construct(CashRegisterDailyBalances $cashRegisterDailyBalances)
    {
        $this->cashRegisterDailyBalances = $cashRegisterDailyBalances;
    }

    public function storeBalance($cashRegisterId, $balance)
    {
        $today = Carbon::today();

        $dailyBalance = $this->cashRegisterDailyBalances
            ->where('cash_register_id', $cashRegisterId)
            ->whereDate('created_at', $today)
            ->first();

        if ($dailyBalance) {
            return $dailyBalance->update(['balance' => $balance]);
        }

        return $this->cashRegisterDailyBalances->create([
            'cash_register_id' => $cashRegisterId,
            'balance' => $balance,
        ]);
    }

    public function updateBalance($cashRegisterId, $balance)
    {
        $today = Carbon::today();

        $dailyBalance = $this->cashRegisterDailyBalances
            ->where('cash_register_id', $cashRegisterId)
            ->latest('created_at')
            ->first();

        if ($dailyBalance && $dailyBalance->created_at->isSameDay($today)) {
            $dailyBalance->update(['balance' => $balance]);
        } else {
            $dailyBalance = $this->cashRegisterDailyBalances->create([
                'cash_register_id' => $cashRegisterId,
                'balance' => $balance,
            ]);
        }

        return $dailyBalance;
    }
    public function updateOrCreateDailyBalance($cashRegisterId, $transactionAdjustment, $inflows, $outflows)
    {
        $currentDate = now()->startOfDay();

        $dailyBalance = CashRegisterDailyBalances::where('cash_register_id', $cashRegisterId)
            ->whereDate('created_at', $currentDate)
            ->first();

        if ($dailyBalance) {
            $dailyBalance->balance += $transactionAdjustment;
            $dailyBalance->inflows += $inflows;
            $dailyBalance->outflows += $outflows;
            $dailyBalance->save();
        } else {
            CashRegisterDailyBalances::create([
                'cash_register_id' => $cashRegisterId,
                'balance' => $transactionAdjustment,
                'inflows' => $inflows,
                'outflows' => $outflows,
                'created_at' => $currentDate
            ]);
        }
    }
}
