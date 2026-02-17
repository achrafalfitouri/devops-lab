<?php

namespace App\Repositories\Contracts;

interface RefundNoteRepositoryInterface
{
    public function update(string $id, array $data);
    public function delete(string $id);
    public function get(string $id);
    public function getAll(); 
    public function create(array $data);
    public function codeExists($code, $excludeId = null);



}
