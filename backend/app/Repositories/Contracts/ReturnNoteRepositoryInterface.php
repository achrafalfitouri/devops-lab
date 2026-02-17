<?php
// app/Repositories/Contracts/ReturnNoteRepositoryInterface.php
namespace App\Repositories\Contracts;

interface ReturnNoteRepositoryInterface
{
    public function getReturnNotes();
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function codeExists($code, $excludeId = null);
}
