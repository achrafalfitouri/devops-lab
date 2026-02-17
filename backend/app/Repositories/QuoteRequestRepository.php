<?php
namespace App\Repositories;

use App\Models\QuoteRequest;
use App\Repositories\Contracts\QuoteRequestRepositoryInterface;
use App\Traits\GeneratesDocumentCode;

class QuoteRequestRepository implements QuoteRequestRepositoryInterface
{

    use GeneratesDocumentCode;

    protected $quote;

    public function __construct(QuoteRequest $quote)
    {
        $this->quote = $quote;
    }

    public function getQuoteRequests()
    {
        return $this->quote->query();
    }

    public function findById($id)
    {
        return $this->quote->find($id);
    }


    public function create(array $data)
    {
        $data['code'] = $this->generateUniqueCode('DD', 'quote_requests');
        return $this->quote->create($data);
    }


    public function update($id, array $data)
    {
        $quote = $this->quote->find($id);
        if ($quote) {
            $quote->update($data);
            return $quote;
        }
        return null;
    }

    public function delete($id)
    {
        $quote = $this->quote->find($id);
        if ($quote) {
            $quote->update(['code' => $quote->code . '-Deleted']);
            $quote->delete();
            return response()->json(['message' => 'QuoteRequest deleted successfully'], 200);
        }
        return false;
    }

    public function codeExists($code, $excludeId = null)
    {
        $query = $this->quote->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
