<?php

namespace App\Exports;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Repositories\Contracts\QuoteRepositoryInterface;
use App\Repositories\Contracts\DocumentItemRepositoryInterface;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class QuoteExport implements FromCollection, WithHeadings
{
    protected $quoteRepository;
    protected $documentItemRepository;
    protected $filters;
    protected $perPage;

    public function __construct(QuoteRepositoryInterface $quoteRepository, DocumentItemRepositoryInterface $documentItemRepository, array $filters, int $perPage)
    {
        $this->quoteRepository = $quoteRepository;
        $this->documentItemRepository = $documentItemRepository;
        $this->filters = $filters;
        $this->perPage = $perPage;
    }

    public function collection()
    {
        $query = $this->quoteRepository->getQuotes();


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
                    $q->orWhere($field, 'LIKE', "%{$searchQuery}%");
                }
            });
        }

        if (!empty($this->filters['date_range'])) {
            [$startDate, $endDate] = explode(',', $this->filters['date_range']);
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        $this->documentItemRepository->setModel(QuoteItem::class);

        $quotes = $query->with(['client', 'user', 'items'])->paginate($this->perPage);

        return collect($quotes->items())->map(function ($quote) {
            return [
                'id' => $quote->id,
                'code' => $quote->code,
                'status' => $quote->status,
                'validity_date' => $quote->validity_date,
                'amount' => $quote->amount,
                'is_taxable' => $quote->is_taxable ? 'Yes' : 'No',
                'tax_amount' => $quote->tax_amount,
                'final_amount' => $quote->final_amount,
                'total_phrase' => $quote->total_phrase,
                'client_name' => $quote->client->legal_name ?? 'N/A',
                'client_ice' => $quote->client->ice ?? 'N/A',
                'user_name' => $quote->user->full_name ?? 'N/A',
                'quote_comment' => $quote->quote_comment,
                'items_descriptions' => $quote->items->map(fn($item) => $item->description)->implode(', '),
                'items_quantities' => $quote->items->map(fn($item) => $item->quantity)->implode(', '),
                'items_prices' => $quote->items->map(fn($item) => $item->price)->implode(', '),
                'items_discounts' => $quote->items->map(fn($item) => $item->discount)->implode(', '),
                'items_amounts' => $quote->items->map(fn($item) => $item->amount)->implode(', '),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'IDENTIFIANT',
            'Citation Code',
            'Statut',
            'Date de validité',
            'Montant',
            'Imposable',
            "Montant de l'impôt",
            "Montant final",
            'Phrase totale',
            "Nom légal du client",
            'Client Ice',
            "Nom complet de l'utilisateur",
            "CITER comment",
            'Éléments (Description)',
            "Éléments (quantité)",
            "Articles (prix)",
            "Articles (réduction)",
            "Articles (montant)",
        ];
    }
}
