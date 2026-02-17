<?php
namespace App\Repositories\Contracts;

interface QuoteItemRepositoryInterface
{
    public function getDocumentItems();
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
