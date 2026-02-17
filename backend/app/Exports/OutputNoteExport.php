<?php

namespace App\Exports;

use App\Models\OutputNote;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OutputNoteExport implements FromCollection, WithHeadings
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
        $query = OutputNote::query();

        $filterableFields = ['user_id', 'client_id', 'status'];
        $searchableFields = ['code', 'output_note', 'total_phrase'];

        // Apply filters
        foreach ($filterableFields as $field) {
            if (!empty($this->filters[$field])) {
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

        if (!empty($this->filters['date_range'])) {
            [$startDate, $endDate] = explode(',', $this->filters['date_range']);
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $outputNotes = $query->with(['user'])->paginate($this->perPage);

        return collect($outputNotes->items())->map(function ($outputNote) {
            return [
                'id' => $outputNote->id,
                'code' => $outputNote->code,
                'description' => $outputNote->description,
                'status' => $outputNote->status,

                'user_name' => $outputNote->user->name ?? 'N/A',
                'created_at' => $outputNote->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $outputNote->updated_at->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'IDENTIFIANT',
            'Code',
            'Description',
            'Statut',
            "Nom d'utilisateur",
            "Créé à",
            "Mise à jour à",
        ];
    }
}
