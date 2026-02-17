<?php
namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoiceExport implements FromCollection, WithHeadings
{
    protected $filters;
    protected $perPage;

    public function __construct(array $filters, int $perPage)
    {
        $this->filters = $filters;
        $this->perPage = $perPage;
    }

    public function collection()
    {
        $query = Invoice::query();

        $filterableFields = ['user_id', 'client_id', 'status'];
        $searchableFields = ['code', 'note'];

        // Apply filters dynamically
        foreach ($filterableFields as $field) {
            if (!empty($this->filters[$field])) {
                $query->where($field, $this->filters[$field]);
            }
        }

        // Apply search filter
        if (!empty($this->filters['search'])) {
            $searchQuery = $this->filters['search'];
            $query->where(function ($q) use ($searchQuery, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$searchQuery}%");
                }
            });
        }

        // Apply date range filter if provided
        if (!empty($this->filters['date_range'])) {
            [$startDate, $endDate] = explode(',', $this->filters['date_range']);
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Include related models and paginate results
        $invoices = $query->with(['client', 'user'])->paginate($this->perPage);

        // Transform results into a collection
        return collect($invoices->items())->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'code' => $invoice->code,
                'amount' => $invoice->amount,
                'discount' => $invoice->discount,
                'discounted_amount' => $invoice->amount - ($invoice->discount ?? 0),
                'due_date' => $invoice->due_date,
                'is_taxable' => $invoice->is_taxable ? 'Yes' : 'No',
                'tax_amount' => $invoice->tax_amount,
                'final_amount' => $invoice->final_amount,
                'client_name' => $invoice->client->legal_name ?? 'N/A',
                'user_name' => $invoice->user->name ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
         'IDENTIFIANT',
'Code',
'Montant',
'Rabais',
"Montant réduit",
"Date d'échéance",
'Imposable',
"Montant de l'impôt",
"Montant final",
"Nom du client",
"Nom d'utilisateur",
        ];
    }
}
