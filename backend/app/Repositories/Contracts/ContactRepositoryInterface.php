<?php

namespace App\Repositories\Contracts;

interface ContactRepositoryInterface
{
    public function getAllContacts();
    public function getById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
