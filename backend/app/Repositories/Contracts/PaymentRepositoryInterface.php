<?php

namespace App\Repositories\Contracts;

interface PaymentRepositoryInterface
{
    public function getPayments();
    public function getRecoveries();
    public function getById(string $id);
    public function getRecoveryById(string $id);
    public function create(array $data);
    public function createRecovery(array $data);
    public function update(string $id, array $data);
    public function updateRecovery(string $id, array $data);
    public function delete(string $id);
    public function deleteRecovery(string $id);

    public function getTotalTurnover(int $year,$client);
    public function getRealTurnover(int $year,$client);
    public function getRecovery(int $year,$client);

    public function getTotalTurnoverByClientType(int $year,$client);
    // public function getTopCitiesByTurnover(int $year);
    public function getTopActivitySectorsByTurnover(int $year,$client);
    public function getTopClientsByTurnover(int $year,$client);

    public function getInvoiceTurnover($year,$client);
    public function getOrderReceiptTurnover($year,$client);
    public function getQuery();
    public function sumPaymentsExcept($invoiceId, $paymentId);
    public function sumPayments($invoiceId);

}