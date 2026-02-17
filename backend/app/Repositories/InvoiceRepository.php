<?php

namespace App\Repositories;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Traits\GeneratesDocumentCode;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    use GeneratesDocumentCode;

    protected $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }
    public function codeExists($code, $excludeId = null)
    {
        $query = $this->invoice->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
    public function getInvoices()
    {
        return $this->invoice->query();
    }

    public function findById($id)
    {
        return $this->invoice->find($id);
    }

    public function create(array $data)
    {
        $data['code'] = $this->generateUniqueCode('F', 'invoices');
        return $this->invoice->create($data);
    }

    public function update($id, array $data)
    {
        $invoice = $this->invoice->find($id);
        if ($invoice) {
            $invoice->update($data);
            return $invoice;
        }
        return null;
    }

    public function delete($id)
    {
        $invoice = $this->invoice->find($id);
        if ($invoice) {
            $invoice->update(['code' => $invoice->code . '-deleted']);

            $invoice->delete();
            return true;
        }
        return false;
    }
}
