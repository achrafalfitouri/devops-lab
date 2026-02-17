<?php

namespace App\Repositories\Contracts;

interface DeliveryNoteRepositoryInterface
{
    public function getDeliveryNotes();
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function codeExists($code, $excludeId = null);

}
