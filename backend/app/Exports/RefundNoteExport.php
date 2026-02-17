<?php

namespace App\Exports;

use App\Models\RefundNote;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RefundNoteExport implements FromCollection, WithHeadings
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
        $query = RefundNote::query();


        $filterableFields = ['user_id', 'client_id', 'status'];
        $searchableFields = ['code', 'total_phrase'];

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
                'credit_comment' => $note->credit_comment,
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
            "Commentaire de crédit",
            'Statut',
            "Nom du client",
            "Nom d'utilisateur",
        ];
    }
}
