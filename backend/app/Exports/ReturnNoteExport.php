<?php

namespace App\Exports;

use App\Models\ReturnNote;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReturnNoteExport implements FromCollection, WithHeadings
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
        $query = ReturnNote::query();

        $filterableFields = ['user_id', 'client_id', 'created_at', 'status'];
        $searchableFields = ['code', 'note', 'total_phrase'];

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
                    $q->orWhere($field, 'LIKE', "%$searchQuery%");
                }
            });
        }
        if (!empty($this->filters['date_range'])) {
            [$startDate, $endDate] = explode(',', $this->filters['date_range']);
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        $note = $query->with(['client', 'user'])->paginate($this->perPage);

        return $query->get();


        // Map the data for each row in the export
        return collect($note->items())->map(function ($note) {
            return [
                'Code' => $note->code,
                'Amount' => $note->amount,
                'Taxable' => $note->is_taxable ? 'Yes' : 'No',
                'Tax Amount' => $note->tax_amount,
                'Final Amount' => $note->final_amount,
                'Total Phrase' => $note->total_phrase,
                'status' => $note->status,

                'Client' => $note->client->legal_name ?? 'N/A',
                'User' => $note->user->name ?? 'N/A',
                'Created At' => $note->created_at->format('Y-m-d'),

            ];
        });
    }

    // Define headings for the export
    public function headings(): array
    {
        return [
            'Code',
            'Montant',
            'Imposable',
            "Montant de l'impôt",
            "Montant final",
            'Phrase totale',
            'Client',
            'Utilisateur',
            "Créé à",
            'statut'
        ];
    }
}
