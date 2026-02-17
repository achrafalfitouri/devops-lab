<?php
// app/Repositories/Contracts/QuoteRepositoryInterface.php
namespace App\Repositories\Contracts;

interface QuoteRepositoryInterface
{
    public function getQuotes();
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function codeExists($code, $excludeId = null);
}
