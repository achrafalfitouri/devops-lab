<?php

namespace App\Repositories;

use App\Models\QuoteItem;
use App\Repositories\Contracts\QuoteItemRepositoryInterface;
use App\Services\ItemLogger;
use Illuminate\Support\Facades\Auth;

class QuoteItemRepository implements QuoteItemRepositoryInterface
{
    protected $model;

    public function __construct(QuoteItem $documentItem)
    {
        $this->model = $documentItem;
    }

    public function getDocumentItems()
    {
        return $this->model->query();
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function create(array $data)
    {
        $item = $this->model->create($data);

        $logger = new ItemLogger();
        $logger->logCreate($item, $item->toArray());

        return $item;
    }

    public function update($id, array $data)
    {
        $item = $this->model->find($id);
        if (! $item) {
            return null;
        }

        $oldValues = $item->toArray();

        $item->fill($data);

        if (! $item->isDirty()) {
            return $item;
        }

        $item->save();

        $logger = new ItemLogger();
        $logger->logUpdate($item, $oldValues, $item->toArray());

        return $item;
    }

    public function delete($id)
    {
        $item = $this->model->find($id);
        if (! $item) {
            return false;
        }

        $oldValues = $item->toArray();

        $item->delete();

        $logger = new ItemLogger();
        $logger->logDelete($item, $oldValues);

        return true;
    }
}
