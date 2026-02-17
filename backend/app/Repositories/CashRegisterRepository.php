<?php

namespace App\Repositories;

use App\Models\CashRegister;
use App\Repositories\Contracts\CashRegisterRepositoryInterface;
use App\Repositories\Contracts\CashLogRepositoryInterface;

use Illuminate\Support\Facades\DB;

class CashRegisterRepository implements CashRegisterRepositoryInterface
{
    protected $CashRegister;
    protected $CashLogRepository;

    public function __construct(CashRegister $CashRegister, CashLogRepositoryInterface $CashLogRepository)
    {
        $this->CashRegister = $CashRegister;
        $this->CashLogRepository = $CashLogRepository;
    }
    public function createCashRegister(array $data)
    {
        $currentYear = date('y');
        $latestentry = $this->CashRegister->orderBy('code', 'desc')->first();


        if ($latestentry && isset($latestentry->code)) {
            preg_match('/CA-(\d{2})-(\d+)/', $latestentry->code, $matches);

            $latestYear = $matches[1];
            $latestCounter = (int) $matches[2];

            if ($latestYear == $currentYear) {
                $newCounter = $latestCounter + 1;
            } else {
                $newCounter = 0;
            }
        } else {
            $newCounter = 3200;
        }

        $data['code'] = 'CA-' . $currentYear . '-' . $newCounter;

        return $this->CashRegister->create($data);
    }


    public function updateCashRegister($id, array $data)
    {
        $cashRegister = CashRegister::findOrFail($id);
        $cashRegister->update($data);
        return $cashRegister;
    }


    public function findById($id)
    {
        return $this->CashRegister->find($id);
    }


    public function softDeleteCashRegister($id)
    {
        $cashRegister = CashRegister::findOrFail($id);
        return $cashRegister->delete();
    }

    public function getCashTransactionTypes($cash_register_type_id = null)
    {
        $query = DB::table('cash_transaction_types')->select('id', 'name', 'sign');

        if ($cash_register_type_id) {
            $query->where('cash_register_type_id', $cash_register_type_id);
        }

        return $query->get();
    }

    public function getAll(array $filters = [])
    {
        return CashRegister::with('transactions.transactionType');
    }
}
