<?php
namespace App\Repositories;

use App\Models\Quote;
use App\Repositories\Contracts\QuoteRepositoryInterface;
use App\Traits\GeneratesDocumentCode;

class QuoteRepository implements QuoteRepositoryInterface
{
    use GeneratesDocumentCode;

    protected $quote;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }

    public function getQuotes()
    {
        return $this->quote->query();
    }

    public function findById($id)
    {
        return $this->quote->find($id);
    }


    public function create(array $data)
    {
        $data['code'] = $this->generateUniqueCode('D', 'quotes');
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
            return response()->json(['message' => 'Quote deleted successfully'], 200);
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
