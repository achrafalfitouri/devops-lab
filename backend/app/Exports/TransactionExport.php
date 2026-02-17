<?php

namespace App\Exports;

use App\Models\CashTransaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TransactionExport implements FromCollection, WithHeadings
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
        $query = CashTransaction::with(['transactionType', 'cashRegister', 'user']);

        // Apply filters
        if (!empty($this->filters['cash_register_id'])) {
            $query->where('cash_register_id', $this->filters['cash_register_id']);
        }
        if (!empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }
        if (!empty($this->filters['cash_transaction_type_id'])) {
            $query->where('cash_transaction_type_id', $this->filters['cash_transaction_type_id']);
        }
        if (!empty($this->filters['date_range'])) {
            $dateRange = explode(' to ', $this->filters['date_range']);
            if (count($dateRange) === 2) {
                $startDate = date('Y-m-d', strtotime($dateRange[0]));
                $endDate = date('Y-m-d', strtotime($dateRange[1]));
                $query->whereBetween('date', [$startDate, $endDate]);
            }
        }
        if (!empty($this->filters['search'])) {
            $searchQuery = $this->filters['search'];
            $query->where(function ($q) use ($searchQuery) {
                $q->whereHas('user', function ($userQuery) use ($searchQuery) {
                    $userQuery->where('first_name', 'LIKE', "%$searchQuery%")
                        ->orWhere('last_name', 'LIKE', "%$searchQuery%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$searchQuery%"]);
                })->orWhere('name', 'LIKE', "%$searchQuery%");
            });
        }

        $transactions = $query->paginate($this->perPage);

        return collect($transactions->items())->map(function ($transaction) {
            return [
                'name' => $transaction->name,
                'amount' => $transaction->amount,
                'comment' => $transaction->comment,
                'code' => $transaction->code,
                'date' => $transaction->date,
                'transaction_type' => $transaction->transactionType->name ?? 'Unknown',
                'cash_register' => $transaction->cashRegister->name ?? 'Unknown',
                'user' => $transaction->user ? $transaction->user->first_name . ' ' . $transaction->user->last_name : 'Unknown',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nom',
            'Montant',
            'Commentaire',
            'Code',
            'Date',
            'Type de transaction',
            'Caisse',
            'Utilisateur',
        ];
    }
}
