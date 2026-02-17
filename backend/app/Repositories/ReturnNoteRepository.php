<?php
namespace App\Repositories;

use App\Models\ReturnNote;
use App\Repositories\Contracts\ReturnNoteRepositoryInterface;
use App\Traits\GeneratesDocumentCode;

class ReturnNoteRepository implements ReturnNoteRepositoryInterface
{
    use GeneratesDocumentCode;

    protected $returnNote;

    public function __construct(ReturnNote $returnNote)
    {
        $this->returnNote = $returnNote;
    }

    public function getReturnNotes()
    {
        return $this->returnNote->query();
    }

    public function findById($id)
    {
        return $this->returnNote->find($id);
    }


    public function create(array $data)
    {
        $data['code'] = $this->generateUniqueCode('BR', 'return_notes');
        return $this->returnNote->create($data);
    }

    public function update($id, array $data)
    {
        $returnNote = $this->returnNote->find($id);
        if ($returnNote) {
            $returnNote->update($data);
            return $returnNote;
        }
        return null;
    }

    public function delete($id)
    {
        $returnNote = $this->returnNote->find($id);
        if ($returnNote) {
            $returnNote->update(['code' => $returnNote->code . '-deleted']);
            $returnNote->delete();
            return true;
        }
        return false;
    }
    public function codeExists($code, $excludeId = null)
    {
        $query = $this->returnNote->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
