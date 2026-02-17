<?php

namespace App\Repositories;

use App\Models\CashTransaction;
use App\Models\Client;
use App\Models\User;
use App\Models\UserCashRegister;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use App\Repositories\Contracts\CashLogRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class TransactionRepository implements TransactionRepositoryInterface
{
    protected $trans;
    protected $cashLogRepository;

    public function __construct(CashTransaction $trans, CashLogRepositoryInterface $cashLogRepository)
    {
        $this->trans = $trans;
        $this->cashLogRepository = $cashLogRepository;
    }


    public function createTransaction(array $data)
    {
        $currentYear = date('y');
        $latestentry = $this->trans->orderBy('id', 'desc')->first();

        if ($latestentry && isset($latestentry->code)) {
            preg_match('/CAT-(\d{2})-(\d+)/', $latestentry->code, $matches);

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

        $data['code'] = 'CAT-' . $currentYear . '-' . $newCounter;

        return $this->trans->create($data);
    }


    public function updateTransaction($id, array $data)
    {
        $client = $this->trans->find($id);

        if ($client) {
            $oldValue = $client->toArray();
            $client->update($data);
            $newValue = $client->toArray();

            $this->cashLogRepository->createLog(
                'update',
                $oldValue,
                $newValue,
                Auth::id(),
                $id
            );

            return $client;
        }
    }

    public function softDeleteTransaction($id)
    {
        $trans = $this->trans->withTrashed()->find($id);

        if ($trans) {
            $oldValue = $trans->toArray();

            $trans->delete();

            $this->cashLogRepository->createLog(
                'delete',
                $oldValue,
                null,
                Auth::check() ? Auth::id() : null,
                $id
            );

            return true;
        }
    }


    public function getAll(array $filters = [])
    {
        $query = $this->trans->query();

        return $query;
    }
    public function getCashTransactionTypes()
    {
        $query = DB::table('cash_transaction_types')->select('id', 'name', 'sign');


        return $query->get();
    }
    public function getCashRegisters()
    {
        $query = DB::table('cash_registers')->select('id', 'name', 'code', 'balance',);

        return $query->get();
    }
    public function getUserCashRegisters()
    {
        $query = UserCashRegister::select('id', 'user_id', 'cash_register_id');

        return $query->get();
    }
    public function getClients()
    {
        $query = Client::select('id', 'legal_name');
        return $query->get();
    }
    public function getUsers()
    {
        $query = User::select('id', 'full_name');
        return $query->get();
    }
    public function findTransactionById($id)
{
    return CashTransaction::where('id', $id)
        ->whereNull('deleted_at') 
        ->first();
}
}
