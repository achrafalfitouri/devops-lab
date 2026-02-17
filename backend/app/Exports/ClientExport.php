<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class ClientExport implements FromCollection, WithHeadings
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
        $query = Client::query();

        $filterableFields = ['gamut_id', 'status_id', 'client_type_id', 'city_id', 'business_sector_id'];
        $searchableFields = ['code', 'legal_name', 'trade_name'];

        foreach ($filterableFields as $field) {
            if (!empty($this->filters[$field])) {
                $query->where($field, $this->filters[$field]);
            }
        }

        if (!empty($this->filters['search'])) {
            $searchQuery = $this->filters['search'];
            $query->where(function ($q) use ($searchQuery, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'REGEXP', $searchQuery);
                }
            });
        }

        $clients = $query->with('businessSector', 'status', 'gamut')
            ->paginate($this->perPage);


        return collect($clients->items())->map(function ($client) {
            return [
                'code' => $client->code,
                'legal_name' => $client->legal_name,
                'trade_name' => $client->trade_name,
                'business_sector' => $client->businessSector->name ?? 'N/A',
                'gamut' => $client->gamut->name ?? 'N/A',
                'Type' => $client->clientType->name ?? 'N/A',
                'ice' => $client->ice,
                'if' => $client->if,
                'tp' => $client->tp,
                'email' => $client->email,
                'phone_number' => $client->phone_number,
                'address' => $client->address,

                'city' => $client->clientType->name ?? 'N/A',
                'status' => $client->status->name ?? 'N/A',

            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Legal name',
            'Trade name',
            'Activity sector',
            'Gammut',
            'Kind',
            'Ice',
            'If',
            'Tp',
            'E-mail',
            'Phone number',
            'Address',
            'Status',
        ];
    }
}
