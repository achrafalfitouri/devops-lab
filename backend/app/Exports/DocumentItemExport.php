<?php

namespace App\Exports;

use App\Models\DocumentItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DocumentItemExport implements FromCollection, WithHeadings
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
        $query = DocumentItem::query();

        // Apply search filter
        if (!empty($this->filters['search'])) {
            $query->where('description', 'LIKE', "%{$this->filters['search']}%");
        }

        // $filterableFields = ['price', 'quantity', 'discount'];
        // foreach ($filterableFields as $field) {
        //     if (!empty($this->filters[$field])) {
        //         $query->where($field, $this->filters[$field]);
        //     }
        // }

        // Apply date range filter if provided
        if (!empty($this->filters['date_range'])) {
            [$startDate, $endDate] = explode(',', $this->filters['date_range']);
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Paginate the results
        $documentItems = $query->paginate($this->perPage);

        // Transform results into a collection
        return collect($documentItems->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'description' => $item->description,
                'price' => $item->price,
                'amount' => $item->amount,
                'quantity' => $item->quantity,
                'discount' => $item->discount,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'IDENTIFIANT',
            'Description',
            'Prix',
            'Montant',
            'Quantité',
            'Rabais',
        ];
    }
}
