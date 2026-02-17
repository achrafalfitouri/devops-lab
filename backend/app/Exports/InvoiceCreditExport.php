<?php
namespace App\Exports;

use App\Models\InvoiceCredit;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
class InvoiceCreditExport implements FromCollection, WithHeadings
{
    protected $filters;
    protected $perPage;

    public function __construct(array $filters, int $perPage = 15)
    {
        $this->filters = $filters;
        $this->perPage = $perPage;
    }

    public function collection(): Collection
    {
        $query = InvoiceCredit::query();

        $filterableFields = ['user_id', 'client_id', 'created_at', 'status'];
        $searchableFields = ['code', 'note'];

        foreach ($filterableFields as $field) {
            if (!empty($this->filters[$field])) {
                $query->where($field, $this->filters[$field]);
            }
        }

        // Apply search query
        if (!empty($this->filters['search'])) {
            $searchQuery = $this->filters['search'];
            $query->where(function ($q) use ($searchQuery, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$searchQuery}%");
                }
            });
        }

        // Apply date range filter
        if (!empty($this->filters['date_range'])) {
            [$startDate, $endDate] = explode(',', $this->filters['date_range']);
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Load relationships and retrieve paginated data
        $invoiceCredits = $query->with(['client', 'user'])->paginate($this->perPage);

        // Transform results into a collection
        return new EloquentCollection(
            collect($invoiceCredits->items())->map(function ($credit) {
                return [
                    'ID' => $credit->id,
                    'Code' => $credit->code,
                    'Amount' => $credit->amount,
                    'Discount' => $credit->discount,
                    'Discounted Amount' => $credit->discounted_amount,
                    'Taxable' => $credit->is_taxable ? 'Yes' : 'No',
                    'Tax Amount' => $credit->tax_amount,
                    'Final Amount' => $credit->final_amount,
                    'Total Phrase' => $credit->total_phrase,
                    'Status' => $credit->status,
                    'Client Name' => $credit->client->legal_name ?? 'N/A',
                    'User Name' => $credit->user->name ?? 'N/A',
                ];
            })
        );
    }

    public function headings(): array
    {
        return [
          'IDENTIFIANT',
'Code',
'Montant',
'Rabais',
"Montant réduit",
'Imposable',
"Montant de l'impôt",
"Montant final",
'Phrase totale',
'Statut',
"Nom du client",
"Nom d'utilisateur",
        ];
    }
}
