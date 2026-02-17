<?php

namespace App\Repositories\Contracts;

interface DocumentItemRepositoryInterface
{
   
    public function setModel(string $model);

   
    public function getAll();

    
    public function findById($id);

   
    public function create(array $data);

    
    public function update($id, array $data);

    public function delete($id);
}
