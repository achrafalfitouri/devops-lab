<?php

namespace App\Repositories;



use App\Repositories\Contracts\DocumentItemRepositoryInterface;
use Exception;

class DocumentItemRepository implements DocumentItemRepositoryInterface
{
    protected $model;

    public function __construct() {}


    public function setModel(string $model)
    {
        if (!class_exists($model)) {
            throw new Exception("Model $model does not exist.");
        }

        $this->model = app($model);
        return $this;
    }



    public function getAll()
    {
        return $this->model->query()->get();
    }


    public function findById($id)
    {
        return $this->model->find($id);
    }


    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        unset($data['id']);

        $record = $this->model->query()->find($id);

        if (!$record) {
            throw new \Exception("Update failed: Record with ID $id not found.");
        }

        $record->fill($data)->save();

        return $record;
    }



    public function delete($id)
    {
        $item = $this->findById($id);

        if (!$item) {
            return false;
        }

        return $item->delete();
    }
}
