<?php

namespace App\Exports;

use App\Models\DeliveryNote;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DeliveryNoteExport implements FromCollection, WithHeadings
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
        $query = DeliveryNote::query();

        $filterableFields = ['user_id', 'client_id', 'status','is_taxable'];
        $searchableFields = ['code', 'delivery_note', 'total_phrase'];

        // Apply filters
        foreach ($filterableFields as $field) {
            if (isset($this->filters[$field])) {
                $query->where($field, $this->filters[$field]);
            }
        }


        // Apply search functionality
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
        $deliveryNotes = $query->with(['client', 'user'])->paginate($this->perPage);

        // Map the results to the desired format
        return collect($deliveryNotes->items())->map(function ($deliveryNote) {
            return [
                'id' => $deliveryNote->id,
                'code' => $deliveryNote->code,
                'amount' => $deliveryNote->amount,
                'is_taxable' => $deliveryNote->is_taxable ? 'Yes' : 'No',
                'tax_amount' => $deliveryNote->tax_amount,
                'final_amount' => $deliveryNote->final_amount,
                'total_phrase' => $deliveryNote->total_phrase,
                'delivery_comment' => $deliveryNote->delivery_note,
                'status' => $deliveryNote->status,
                'client_name' => $deliveryNote->client->legal_name ?? 'N/A',
                'user_name' => $deliveryNote->user->name ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'IDENTIFIANT',
            'Code',
            'Montant',
            'Imposable',
            "Montant de l'impôt",
            "Montant final",
            'Phrase totale',
            "Commentaire de livraison",
            'Statut',
            "Nom du client",
            "Nom d'utilisateur",
        ];
    }
}
