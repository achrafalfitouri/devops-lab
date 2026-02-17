<?php
namespace App\Repositories;

use App\Models\InvoiceCredit;
use App\Repositories\Contracts\InvoiceCreditRepositoryInterface;
use App\Traits\GeneratesDocumentCode;

class InvoiceCreditRepository implements InvoiceCreditRepositoryInterface
{
    use GeneratesDocumentCode;

    protected $invoiceCredit;

    public function __construct(InvoiceCredit $invoiceCredit)
    {
        $this->invoiceCredit = $invoiceCredit;
    }
    public function codeExists($code, $excludeId = null)
    {
        $query = $this->invoiceCredit->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
    public function getInvoiceCredits()
    {
        return $this->invoiceCredit->query();
    }

    public function findById($id)
    {
        return $this->invoiceCredit->find($id);
    }

    public function create(array $data)
    {
        $data['code'] = $this->generateUniqueCode('FA', 'invoice_credits');
        return $this->invoiceCredit->create($data);
    }




    public function update($id, array $data)
    {
        $invoiceCredit = $this->invoiceCredit->find($id);
        if ($invoiceCredit) {
            $invoiceCredit->update($data);
            return $invoiceCredit;
        }
        return null;
    }

    public function delete($id)
    {
        $invoiceCredit = $this->invoiceCredit->find($id);
        if ($invoiceCredit) {
            $invoiceCredit->update(['code' => $invoiceCredit->code . '-deleted']);

            $invoiceCredit->delete();
            return true;
        }
        return false;
    }
}
