<?php
// app/Repositories/Contracts/InvoiceCreditRepositoryInterface.php
namespace App\Repositories\Contracts;

interface InvoiceCreditRepositoryInterface
{
    public function getInvoiceCredits();
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function codeExists($code, $excludeId = null);

}
