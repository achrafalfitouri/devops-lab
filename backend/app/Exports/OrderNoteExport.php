<?php

namespace App\Exports;

use App\Models\OrderNote;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrderNoteExport implements FromCollection, WithHeadings
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
        $query = OrderNote::query();


        $filterableFields = ['user_id', 'client_id', 'status'];
        $searchableFields = ['code', 'order_note', 'total_phrase'];

        foreach ($filterableFields as $field) {
            if (!empty($this->filters[$field])) {
                $query->where($field, $this->filters[$field]);
            }
        }

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

        $orderNotes = $query->with(['client', 'user'])->paginate($this->perPage);

        return collect($orderNotes->items())->map(function ($note) {
            return [
                'id' => $note->id,
                'code' => $note->code,
                'amount' => $note->amount,
                'is_taxable' => $note->is_taxable ? 'Yes' : 'No',
                'tax_amount' => $note->tax_amount,
                'final_amount' => $note->final_amount,
                'total_phrase' => $note->total_phrase,
                'order_comment' => $note->order_comment,
                'status' => $note->status,
                'client_name' => $note->client->legal_name ?? 'N/A',
                'user_name' => $note->user->name ?? 'N/A',
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
            "Commandez comment",
            'Statut',
            "Nom du client",
            "Nom d'utilisateur",
        ];
    }
}
