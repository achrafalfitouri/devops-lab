<?php

namespace App\Repositories;

use App\Models\DeliveryNote;
use App\Repositories\Contracts\DeliveryNoteRepositoryInterface;
use App\Traits\GeneratesDocumentCode;

class DeliveryNoteRepository implements DeliveryNoteRepositoryInterface
{
    use GeneratesDocumentCode;

    protected $deliveryNote;

    public function __construct(DeliveryNote $deliveryNote)
    {
        $this->deliveryNote = $deliveryNote;
    }
    public function codeExists($code, $excludeId = null)
    {
        $query = $this->deliveryNote->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
    public function getDeliveryNotes()
    {
        return $this->deliveryNote->query();
    }

    public function findById($id)
    {
        return $this->deliveryNote->find($id);
    }


    public function create(array $data)
    {
        $data['code'] = $this->generateUniqueCode('BL', 'delivery_notes');
        return $this->deliveryNote->create($data);
    }

   


    public function update($id, array $data)
    {
        $deliveryNote = $this->deliveryNote->find($id);
        if ($deliveryNote) {
            $deliveryNote->update($data);
            return $deliveryNote;
        }
        return null;
    }

    public function delete($id)
    {
        $deliveryNote = $this->deliveryNote->find($id);
        if ($deliveryNote) {
            $deliveryNote->update(['code' => $deliveryNote->code . '-deleted']);

            $deliveryNote->delete();
            return true;
        }
        return false;
    }
}
