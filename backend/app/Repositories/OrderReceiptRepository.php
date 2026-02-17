<?php
namespace App\Repositories;

use App\Models\OrderReceipt;
use App\Repositories\Contracts\OrderReceiptRepositoryInterface;
use App\Traits\GeneratesDocumentCode;

class OrderReceiptRepository implements OrderReceiptRepositoryInterface
{
    use GeneratesDocumentCode;

    protected $OrderReceipt;

    public function __construct(OrderReceipt $OrderReceipt)
    {
        $this->OrderReceipt = $OrderReceipt;
    }
    public function all()
    {
        return $this->OrderReceipt->query();
    }
    public function codeExists($code, $excludeId = null)
    {
        $query = $this->OrderReceipt->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
    public function find($id)
    {
        return $this->OrderReceipt->find($id);
    }

    public function create(array $data)
    {
        $data['code'] = $this->generateUniqueCode('BR', 'order_receipts');
        return $this->OrderReceipt->create($data);
    }


    public function update($id, array $data)
    {
        $orderReceipt = $this->find($id);
        $orderReceipt->update($data);
        return $orderReceipt;
    }

    public function delete($id)
    {
        $orderReceipt = $this->find($id);
        $orderReceipt->update(['code' => $orderReceipt->code . '-deleted']);

        return $orderReceipt->delete();
    }
    public function getQuery()
{
    return OrderReceipt::query();
}

}
