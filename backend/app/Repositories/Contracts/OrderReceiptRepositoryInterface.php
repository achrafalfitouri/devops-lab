<?php
namespace App\Repositories\Contracts;


interface OrderReceiptRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getQuery();
    public function codeExists($code, $excludeId = null);


}
