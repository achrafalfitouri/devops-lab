<?php

namespace App\Exports;

use App\Models\ProductionNote;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductionNoteExport implements FromCollection, WithHeadings
{
    protected $filters;
    protected $perPage;

    public function __construct(array $filters, int $perPage = 15)
    {
        $this->filters = $filters;
        $this->perPage = $perPage;
    }

    public function collection()
    {
        $query = ProductionNote::query();


        $filterableFields = ['user_id', 'client_id', 'status'];
        $searchableFields = ['code', 'note'];

        foreach ($filterableFields as $field) {
            if (!empty($this->filters[$field])) {
                $query->where($field, $this->filters[$field]);
            }
        }

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
        $productionNotes = $query->with(['client', 'user'])->paginate($this->perPage);

        return $query->get();

        return collect($productionNotes->items())->map(function ($productionNote) {
            return [
                'ID' => $productionNote->code,
                'Code' => $productionNote->code,
                'Status' => $productionNote->status,
                'Note' => $productionNote->note,
                'Amount' => $productionNote->amount,
                'Client' => $note->client->legal_name ?? 'N/A',
                'User' => $note->user->name ?? 'N/A',
                'Created At' => $productionNote->created_at->format('Y-m-d'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'IDENTIFIANT',
            'Code',
            'Statut',
            'Note',
            'Montant',
            'Client',
            'Utilisateur',
            "Créé à",
        ];
    }
}
