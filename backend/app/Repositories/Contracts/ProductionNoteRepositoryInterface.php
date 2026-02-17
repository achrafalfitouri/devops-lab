<?php

namespace App\Repositories\Contracts;

interface ProductionNoteRepositoryInterface
{
    public function getProductionNotes();
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function codeExists($code, $excludeId = null);

}