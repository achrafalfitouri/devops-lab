<?php

namespace App\Repositories;

use App\Models\RefundNote;
use App\Repositories\Contracts\RefundNoteRepositoryInterface;
use App\Traits\GeneratesDocumentCode;

class RefundNoteRepository implements RefundNoteRepositoryInterface
{
    use GeneratesDocumentCode;

    protected $RefundNote;
    public function __construct(RefundNote $RefundNote)
    {
        $this->RefundNote = $RefundNote;
    }
    public function update(string $id, array $data)
    {
        $refundNote = RefundNote::findOrFail($id);
        $refundNote->update($data);
        return $refundNote;
    }

    public function delete(string $id)
    {
        $refundNote = RefundNote::findOrFail($id);
        $refundNote ->update(['code' => $refundNote->code . '-deleted']);
        $refundNote->delete();
        return response()->json(['message' => 'Refund note deleted successfully'], 200);
    }
    public function get(string $id)
    {
        return RefundNote::findOrFail($id);
    }
    public function getAll()
    {
        return RefundNote::query();
    }

    public function create(array $data)
    {
        $data['code'] = $this->generateUniqueCode('BR', 'refund_notes');
        return $this->RefundNote->create($data);
    }
   
    public function codeExists($code, $excludeId = null)
    {
        $query = $this->RefundNote->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
