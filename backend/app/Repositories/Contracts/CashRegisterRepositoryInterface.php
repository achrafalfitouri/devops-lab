<?php

namespace App\Repositories\Contracts;

interface CashRegisterRepositoryInterface
{
    public function createCashRegister(array $data);
    public function updateCashRegister($id, array $data);
    public function findById($id);
    public function softDeleteCashRegister($id);
    public function getCashTransactionTypes($cash_register_type_id = null);
    public function getAll(array $filters = []);
}
