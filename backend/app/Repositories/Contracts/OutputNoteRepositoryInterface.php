<?php
// app/Repositories/Contracts/OutputNoteRepositoryInterface.php
namespace App\Repositories\Contracts;

interface OutputNoteRepositoryInterface
{
    public function getOutputNotes();
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getQuery();
    public function codeExists($code, $excludeId = null);

}
