<?php

namespace App\Exports;

use App\Models\OrderReceipt;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrderReceiptExport implements FromCollection, WithHeadings
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
        $query = OrderReceipt::query();


        $filterableFields = ['user_id', 'client_id', 'status'];
        $searchableFields = ['code'];

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

        $orderReceipts = $query->with(['client', 'user'])->paginate($this->perPage);

        return collect($orderReceipts->items())->map(function ($receipt) {
            return [
                'ID' => $receipt->id,
                'Code' => $receipt->code,
                'Due Date' => $receipt->due_date,
                'Amount' => $receipt->amount,
                'Discount' => $receipt->discount,
                'Discounted Amount' => $receipt->amount - ($receipt->discount ?? 0),
                'Taxable' => $receipt->is_taxable ? 'Yes' : 'No',
                'Tax Amount' => $receipt->tax_amount,
                'Final Amount' => $receipt->final_amount,
                'Total Phrase' => $receipt->total_phrase,
                'Note' => $receipt->note,
                'Status' => $receipt->status,
                'Client Name' => $receipt->client->legal_name ?? 'N/A',
                'User Name' => $receipt->user->name ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'IDENTIFIANT',
            'Code',
            "Date d'échéance",
            'Montant',
            'Rabais',
            "Montant réduit",
            "Imposable",
            "Montant de l'impôt",
            "Montant final",
            'Phrase totale',
            'Note',
            'Statut',
            "Nom du client",
            "Nom d'utilisateur",
        ];
    }
}
