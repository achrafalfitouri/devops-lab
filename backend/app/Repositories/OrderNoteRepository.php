<?php

namespace App\Repositories;

use App\Models\OrderNote;
use App\Repositories\Contracts\OrderNoteRepositoryInterface;
use App\Services\DocumentLogger;
use App\Traits\GeneratesDocumentCode;

class OrderNoteRepository implements OrderNoteRepositoryInterface
{
    use GeneratesDocumentCode;

    protected $orderNote;
    protected $documentLogger;


    public function __construct(OrderNote $orderNote, DocumentLogger $documentLogger)
    {
        $this->orderNote = $orderNote;
        $this->documentLogger = $documentLogger;
    }
    public function codeExists($code, $excludeId = null)
    {
        $query = $this->orderNote->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function getOrderNotes()
    {
        return $this->orderNote->query();
    }

    public function findById($id)
    {
        return $this->orderNote->find($id);
    }
    public function getLatestByPrefix($prefix, $yearSuffix)
    {
        return $this->orderNote
            ->where('code', 'like', "{$prefix}-{$yearSuffix}-%")
            ->orderBy('id', 'desc')
            ->first();
    }





   public function create(array $data)
{
    $prefix = 'B'; // "Bon de Note" (Order Note) in French
    $currentYear = date('Y');
    $yearSuffix = date('y');

    // Generate unique code with retry logic
    do {
        $latestEntry = $this->orderNote
            ->where('code', 'like', "{$prefix}-{$yearSuffix}-%")
            ->orderBy('code', 'desc')
            ->first();

        $newCounter = 10000;

        if ($latestEntry && isset($latestEntry->code)) {
            preg_match("/{$prefix}-{$yearSuffix}-(\d+)/", $latestEntry->code, $matches);

            if (isset($matches[1])) {
                $latestCounter = (int)$matches[1];
                $newCounter = $latestCounter + 1;
            }
        }

        $generatedCode = "{$prefix}-{$yearSuffix}-{$newCounter}";

    } while ($this->codeExists($generatedCode));

    $data['code'] = $generatedCode;

    return $this->orderNote->create($data);
}

    public function update($id, array $data)
    {
        $orderNote = $this->orderNote->find($id);
        if ($orderNote) {
            $oldValues = $orderNote->toArray();
            $orderNote->update($data);
            $this->documentLogger->logUpdate($orderNote, $oldValues, $orderNote->toArray());
            return $orderNote;
        }
        return null;
    }

    public function delete($id)
    {
        $orderNote = $this->orderNote->find($id);

        if ($orderNote) {
            $oldValues = $orderNote->toArray();
            $orderNote->update(['code' => $orderNote->code . '-deleted']);
            $orderNote->delete();
            $documentLogger = new DocumentLogger();
            $documentLogger->logDelete($orderNote, $oldValues);

            return true;
        }
        return false;
    }
}
