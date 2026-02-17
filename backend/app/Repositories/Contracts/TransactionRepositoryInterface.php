<?php

namespace App\Repositories\Contracts;

interface TransactionRepositoryInterface
{

    public function createTransaction(array $data);
    public function updateTransaction($id, array $data);
    public function softDeleteTransaction($id);
    public function getAll(array $filters = []);
    public function getCashTransactionTypes();
    public function getCashRegisters();
    public function getUserCashRegisters();
    public function getClients();
    public function getUsers();
    public function findTransactionById($id);
}
