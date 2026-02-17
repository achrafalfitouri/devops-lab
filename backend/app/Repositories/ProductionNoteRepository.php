<?php

namespace App\Repositories;
namespace App\Repositories;

use App\Models\ProductionNote;
use App\Repositories\Contracts\ProductionNoteRepositoryInterface;
use App\Traits\GeneratesDocumentCode;

class ProductionNoteRepository implements ProductionNoteRepositoryInterface
{
    use GeneratesDocumentCode;

    protected $productionNote;

    public function __construct(ProductionNote $productionNote)
    {
        $this->productionNote = $productionNote;
    }

    public function getProductionNotes()
    {
        return $this->productionNote->query();
    }
    public function codeExists($code, $excludeId = null)
    {
        $query = $this->productionNote->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
    public function findById($id)
    {
        return $this->productionNote->find($id);
    }

    public function create(array $data)
    {
        $data['code'] = $this->generateUniqueCode('BP', 'production_notes');
        return $this->productionNote->create($data);
    }
   
    public function update($id, array $data)
    {
        $productionNote = $this->productionNote->find($id);
        if ($productionNote) {
            $productionNote->update($data);
            return $productionNote;
        }
        return null;
    }

    public function delete($id)
    {
        $productionNote = $this->productionNote->find($id);
        if ($productionNote) {
            $productionNote->update(['code' => $productionNote->code . '-deleted']);
            $productionNote->delete();
            return true;
        }
        return false;

    }
}
