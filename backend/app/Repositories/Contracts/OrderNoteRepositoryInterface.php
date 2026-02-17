<?php
// app/Repositories/Contracts/OrderNoteRepositoryInterface.php
namespace App\Repositories\Contracts;

interface OrderNoteRepositoryInterface
{
    public function getOrderNotes();
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function codeExists($code, $excludeId = null);
    public function getLatestByPrefix($prefix, $yearSuffix);


}
