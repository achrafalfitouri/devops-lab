<?php

namespace App\Repositories;

use App\Models\OutputNote;
use App\Repositories\Contracts\OutputNoteRepositoryInterface;
use App\Traits\GeneratesDocumentCode;

class OutputNoteRepository implements OutputNoteRepositoryInterface
{
    use GeneratesDocumentCode;

    protected $outputNote;

    public function __construct(OutputNote $outputNote)
    {
        $this->outputNote = $outputNote;
    }

    public function getOutputNotes()
    {
        return $this->outputNote->query();
    }
    public function codeExists($code, $excludeId = null)
    {
        $query = $this->outputNote->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
    public function findById($id)
    {
        return $this->outputNote->find($id);
    }

    public function create(array $data)
    {
        $data['code'] = $this->generateUniqueCode('BS', 'output_notes');
        return $this->outputNote->create($data);
    }


    public function update($id, array $data)
    {
        $outputNote = $this->outputNote->find($id);
        if ($outputNote) {
            $outputNote->update($data);
            return $outputNote;
        }
        return null;
    }

    public function delete($id)
    {
        $outputNote = $this->outputNote->find($id);
        if ($outputNote) {
            $outputNote->update(['code' => $outputNote->code . '-deleted']);
            $outputNote->delete();
            return response()->json(['message' => 'Output note deleted successfully'], 200);
        }

        return false;
    }
    public function getQuery()
    {
        return OutputNote::query();
    }
}
