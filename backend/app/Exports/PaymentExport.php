<?php

namespace App\Exports;

use App\Models\Payment;
use App\Models\Recovery;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PaymentExport implements WithMultipleSheets
{
    protected $filters;
    protected $perPage;

    public function __construct(array $filters, int $perPage)
    {
        $this->filters = $filters;
        $this->perPage = $perPage;
    }

    public function sheets(): array
    {
        return [
            new PaymentsSheet($this->filters, $this->perPage),
            new RecoveriesSheet($this->filters, $this->perPage),
        ];
    }
}

class PaymentsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $filters;
    protected $perPage;

    public function __construct(array $filters, int $perPage)
    {
        $this->filters = $filters;
        $this->perPage = $perPage;
    }

    public function title(): string
    {
        return 'Paiements';
    }

    public function collection()
    {
        $query = Payment::query();

        $filterableFields = ['payment_type_id', 'status', 'user_id', 'invoice_id'];
        $searchableFields = ['comment'];

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
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $payments = $query->with(['paymentType', 'client', 'invoice', 'recovery'])->paginate($this->perPage);

        return collect($payments->items())->map(function ($payment) {
            // Get recovery data if recovery_id exists
            $checkNumber = $payment->recovery ? ($payment->recovery->check_number ?? 'N/A') : 'N/A';
            $checkDate = $payment->recovery && $payment->recovery->check_date 
                ? ($payment->recovery->check_date instanceof \DateTime ? $payment->recovery->check_date->format('Y-m-d') : $payment->recovery->check_date)
                : 'N/A';
            $wireTransferNumber = $payment->recovery ? ($payment->recovery->wire_transfer_number ?? 'N/A') : 'N/A';
            $effectDate = $payment->recovery && $payment->recovery->effect_date
                ? ($payment->recovery->effect_date instanceof \DateTime ? $payment->recovery->effect_date->format('Y-m-d') : $payment->recovery->effect_date)
                : 'N/A';
            $effectNumber = $payment->recovery ? ($payment->recovery->effect_number ?? 'N/A') : 'N/A';

            return [
                'ID' => $payment->id,
                'Code' => $payment->code ?? 'N/A',
                'Date' => $payment->date instanceof \DateTime ? $payment->date->format('Y-m-d') : ($payment->date ?? 'N/A'),
                'Montant' => $payment->amount,
                'Client' => $payment->client->legal_name ?? 'N/A',
                'Type de paiement' => $payment->paymentType->name ?? 'N/A',
                'Code Recouvrement' => $payment->recovery ? $payment->recovery->code : 'N/A',
                'Numéro de chèque' => $checkNumber,
                'Date de chèque' => $checkDate,
                'Numéro de virement' => $wireTransferNumber,
                'Date d\'effet' => $effectDate,
                'Numéro d\'effet' => $effectNumber,
                'Commentaire' => $payment->comment ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'IDENTIFIANT',
            'Code',
            'Date',
            'Montant',
            'Client',
            'Type de paiement',
            'Code Recouvrement',
            'Numéro de chèque',
            'Date de chèque',
            'Numéro de virement',
            'Date d\'effet',
            'Numéro d\'effet',
            'Commentaire',
        ];
    }
}

class RecoveriesSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $filters;
    protected $perPage;

    public function __construct(array $filters, int $perPage)
    {
        $this->filters = $filters;
        $this->perPage = $perPage;
    }

    public function title(): string
    {
        return 'Recouvrements';
    }

    public function collection()
    {
        $query = Recovery::query();

        $filterableFields = ['payment_type_id', 'client_id'];
        $searchableFields = ['comment', 'code'];

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
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $recoveries = $query->with(['paymentType', 'client'])->paginate($this->perPage);

        return collect($recoveries->items())->map(function ($recovery) {
            return [
                'ID' => $recovery->id,
                'Code' => $recovery->code ?? 'N/A',
                'Date' => $recovery->date instanceof \DateTime ? $recovery->date->format('Y-m-d') : ($recovery->date ?? 'N/A'),
                'Montant' => $recovery->amount,
                'Solde' => $recovery->recovery_balance,
                'Client' => $recovery->client->legal_name ?? 'N/A',
                'Type de paiement' => $recovery->paymentType->name ?? 'N/A',
                'Numéro de chèque' => $recovery->check_number ?? 'N/A',
                'Date de chèque' => $recovery->check_date instanceof \DateTime ? $recovery->check_date->format('Y-m-d') : 'N/A',
                'Numéro de virement' => $recovery->wire_transfer_number ?? 'N/A',
                'Date d\'effet' => $recovery->effect_date instanceof \DateTime ? $recovery->effect_date->format('Y-m-d') : 'N/A',
                'Numéro d\'effet' => $recovery->effect_number ?? 'N/A',
                'Commentaire' => $recovery->comment ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'IDENTIFIANT',
            'Code',
            'Date',
            'Montant',
            'Solde de recouvrement',
            'Client',
            'Type de paiement',
            'Numéro de chèque',
            'Date de chèque',
            'Numéro de virement',
            'Date d\'effet',
            'Numéro d\'effet',
            'Commentaire',
        ];
    }
}
