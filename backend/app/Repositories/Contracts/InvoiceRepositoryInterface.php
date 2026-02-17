<?php
// app/Repositories/Contracts/InvoiceRepositoryInterface.php
namespace App\Repositories\Contracts;

interface InvoiceRepositoryInterface
{
    public function getInvoices();
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function codeExists($code, $excludeId = null);

}
