<?php

namespace App\Repositories;

use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\PaymentLogRepositoryInterface;
use App\Models\Payment;
use App\Models\Recovery;
use App\Models\PaymentDocument;
use App\Models\Invoice;
use App\Models\ClientType;
use App\Models\BusinessSector;
use App\Models\Client;
use App\Models\OrderReceipt;
use Illuminate\Support\Facades\Auth;

class PaymentRepository implements PaymentRepositoryInterface
{

    protected $payment;
    protected $paymentLogRepository;

    public function __construct(Payment $payment, PaymentLogRepositoryInterface $paymentLogRepository)
    {
        $this->payment = $payment;
        $this->paymentLogRepository = $paymentLogRepository;
    }

    public function getPayments()
    {
        return Payment::query();
    }
    public function getRecoveries()
    {
        return Recovery::query();
    }

    public function getById(string $id)
    {
        return Payment::findOrFail($id);
    }
    public function getRecoveryById(string $id)
    {
        return Recovery::where('id', $id);
    }

    public function create(array $data)
    {
        // Check if recovery_id is provided and validate recovery_balance
        if (isset($data['recovery_id'])) {
            $recovery = Recovery::find($data['recovery_id']);
            
            if (!$recovery) {
                throw new \Exception('Recovery not found');
            }
            
            // Convert to float for accurate comparison
            $paymentAmount = (float) $data['amount'];
            $recoveryBalance = (float) $recovery->recovery_balance;
            
            if ($recoveryBalance <= 0) {
                throw new \Exception('Le solde du recouvrement est à zéro. Impossible de créer le paiement.');
            }

            if ($paymentAmount > $recoveryBalance) {
                throw new \Exception('Le montant du paiement (' . $paymentAmount . ') dépasse le solde disponible du recouvrement (' . $recoveryBalance . ').');
            }
        }

        // Generate code if not provided
        if (!isset($data['code']) || empty($data['code'])) {
            $prefix =  'P';
            $currentYear = date('Y');
            $yearSuffix = date('y');

            $latestEntry = $this->payment->withTrashed()
                ->where('code', 'like', "{$prefix}-{$yearSuffix}-%")
                ->orderBy('code', 'desc')
                ->first();

            $newCounter = 10000;

            if ($latestEntry && isset($latestEntry->code)) {
                preg_match("/{$prefix}-{$yearSuffix}-(\\d+)/", $latestEntry->code, $matches);

                if (isset($matches[1])) {
                    $latestCounter = (int)$matches[1];
                    $newCounter = $latestCounter + 1;
                }
            }

            $data['code'] = "{$prefix}-{$yearSuffix}-{$newCounter}";
        }

        // Extract pivot table data
        $invoiceId = $data['invoice_id'] ?? null;
        $orderReceiptId = $data['order_receipt_id'] ?? null;
        $recoveryId = $data['recovery_id'] ?? null;
        
        // Remove only pivot keys (invoice_id, order_receipt_id) - keep recovery_id for payment table
        unset($data['invoice_id'], $data['order_receipt_id']);

        // Create payment
        $payment = $this->payment->create($data);

        // Store in pivot table if invoice or order receipt is provided
        if ($invoiceId || $orderReceiptId) {
            $pivotData = ['payment_id' => $payment->id];
            
            if ($invoiceId) {
                $pivotData['invoice_id'] = $invoiceId;
            }
            
            if ($orderReceiptId) {
                $pivotData['order_receipt_id'] = $orderReceiptId;
            }
            
            PaymentDocument::create($pivotData);
        }

        // Update recovery balance if recovery is used
        if ($recoveryId && isset($recovery)) {
            $recovery->recovery_balance -= $payment->amount;
            $recovery->save();
        }

        return $payment;
    }

    public function createRecovery(array $data)
    {
        // Generate code if not provided
        if (!isset($data['code']) || empty($data['code'])) {
            $prefix =  'R';
            $currentYear = date('Y');
            $yearSuffix = date('y');

            $latestEntry = Recovery::withTrashed()
                ->where('code', 'like', "{$prefix}-{$yearSuffix}-%")
                ->orderBy('code', 'desc')
                ->first();

            $newCounter = 10000;

            if ($latestEntry && isset($latestEntry->code)) {
                preg_match("/{$prefix}-{$yearSuffix}-(\\d+)/", $latestEntry->code, $matches);

                if (isset($matches[1])) {
                    $latestCounter = (int)$matches[1];
                    $newCounter = $latestCounter + 1;
                }
            }

            $data['code'] = "{$prefix}-{$yearSuffix}-{$newCounter}";
        }

        // Set recovery_balance to amount initially
        $data['recovery_balance'] = $data['amount'];

        return Recovery::create($data);
    }


    public function update(string $id, array $data)
    {
        $payment = Payment::findOrFail($id);
        if ($payment) {
            $oldData = $payment->toArray();
            $oldAmount = (float) $payment->amount;
            
            // If amount is being updated and payment has a recovery, adjust recovery_balance
            if (isset($data['amount']) && $payment->recovery_id) {
                $newAmount = (float) $data['amount'];
                
                if ($newAmount != $oldAmount) {
                    $recovery = Recovery::find($payment->recovery_id);
                    if ($recovery) {
                        $currentBalance = (float) $recovery->recovery_balance;
                        
                        // Step 1: Restore old amount to get available balance
                        $restoredBalance = $currentBalance + $oldAmount;
                        
                        // Step 2: Validate new amount doesn't exceed restored balance
                        if ($newAmount > $restoredBalance) {
                            throw new \Exception('Le nouveau montant (' . $newAmount . ') dépasse le solde disponible du recouvrement (' . $restoredBalance . ').');
                        }
                        
                        // Step 3: Calculate new balance = restored - new amount
                        $recovery->recovery_balance = $restoredBalance - $newAmount;
                        $recovery->save();
                    }
                }
            }
            
            $payment->update($data);
            $newData = $payment->toArray();
            $this->paymentLogRepository->createLog(
                'update',
                $oldData,
                $newData,
                Auth::user()->id,
                $payment->id
            );
            return $payment;
        }
        return null;
    }

    public function updateRecovery(string $id, array $data)
    {
        $recovery = Recovery::findOrFail($id);
        if ($recovery) {
            $oldData = $recovery->toArray();
            
            // If amount is being updated, adjust recovery_balance accordingly
            if (isset($data['amount']) && $data['amount'] != $recovery->amount) {
                $amountDiff = $data['amount'] - $recovery->amount;
                $data['recovery_balance'] = $recovery->recovery_balance + $amountDiff;
            }
            
            $recovery->update($data);
            $newData = $recovery->toArray();
            $this->paymentLogRepository->createLog(
                'update',
                $oldData,
                $newData,
                Auth::user()->id,
                $recovery->id
            );
            return $recovery;
        }
        return null;
    }

    public function delete(string $id)
    {
        $payment = $this->payment->find($id);

        if ($payment) {
            $oldData = $payment->toArray();
            $payment->delete();
            $this->paymentLogRepository->createLog(
                'delete',
                $oldData,
                null,
                Auth::user()->id,
                $payment->id
            );

            return true;
        }
        return false;
    }

    public function deleteRecovery(string $id)
    {
        $recovery = Recovery::find($id);

        if ($recovery) {
            $oldData = $recovery->toArray();
            $recovery->delete();
            $this->paymentLogRepository->createLog(
                'delete',
                $oldData,
                null,
                Auth::user()->id,
                $recovery->id
            );

            return true;
        }
        return false;
    }

    public function sumPaymentsExcept($invoiceId, $paymentId)

    {

        return Payment::where('invoice_id', $invoiceId)

            ->where('id', '!=', $paymentId)

            ->whereNull('deleted_at')

            ->sum('amount');
    }

    public function getTotalTurnover($year, $client)
    {
    $invoiceTurnover = Invoice::selectRaw('MONTH(created_at) as month, SUM(amount) as monthly_turnover')
    ->when($year, fn($q) => $q->whereYear('created_at', $year))
    ->when($client, fn($q) => $q->where('client_id', $client))
    ->groupBy('month')
    ->get()
    ->keyBy('month');

$orderReceiptTurnover = OrderReceipt::selectRaw('MONTH(created_at) as month, SUM(amount) as monthly_turnover')
    ->when($year, fn($q) => $q->whereYear('created_at', $year))
    ->when($client, fn($q) => $q->where('client_id', $client))
    ->groupBy('month')
    ->get()
    ->keyBy('month');


        $monthlyStats = array_fill(1, 12, 0);

        foreach ($monthlyStats as $month => $value) {
            $invoiceAmount = $invoiceTurnover[$month]->monthly_turnover ?? 0;
            $orderReceiptAmount = $orderReceiptTurnover[$month]->monthly_turnover ?? 0;
            $monthlyStats[$month] = $invoiceAmount + $orderReceiptAmount;
        }

        return collect($monthlyStats)->map(function ($turnover, $month) {
            return [
                "month" => $this->translateMonthToFrench($month),
                "total_turnover" => $turnover,
            ];
        })->values();
    }


    public function getInvoiceTurnover($year, $client)
    {
$monthlyPayments = Invoice::selectRaw('MONTH(created_at) as month, SUM(amount) as monthly_payment')
    ->when($year, function ($query, $year) {
        return $query->whereYear('created_at', $year);
    })
    ->when($client, function ($query, $client) {
        return $query->where('client_id', $client);
    })
    ->groupBy('month')
    ->get();


        $monthlyStats = array_fill(1, 12, 0);
        foreach ($monthlyPayments as $data) {
            $monthlyStats[$data->month] = $data->monthly_payment;
        }

        return collect($monthlyStats)->map(function ($amount, $month) {
            return [
                "month" => $this->translateMonthToFrench($month),
                "invoice_turnover" => $amount,
            ];
        })->values();
    }

    public function getOrderReceiptTurnover($year, $client)
    {
      $monthlyPayments = OrderReceipt::selectRaw('MONTH(created_at) as month, SUM(amount) as monthly_payment')
    ->when($year, function ($query, $year) {
        return $query->whereYear('created_at', $year);
    })
    ->when($client, function ($query, $client) {
        return $query->where('client_id', $client);
    })
    ->groupBy('month')
    ->get();


        $monthlyStats = array_fill(1, 12, 0);
        foreach ($monthlyPayments as $data) {
            $monthlyStats[$data->month] = $data->monthly_payment;
        }

        return collect($monthlyStats)->map(function ($amount, $month) {
            return [
                "month" => $this->translateMonthToFrench($month),
                "order_receipt_turnover" => $amount,
            ];
        })->values();
    }

    public function getRealTurnover($year, $client)
    {
     $monthlyPayments = Payment::selectRaw('MONTH(date) as month, SUM(amount) as monthly_payment')
    ->when($year, function ($query, $year) {
        return $query->whereYear('date', $year);
    })
    ->when($client, function ($query, $client) {
        return $query->where('client_id', $client);
    })
    ->groupBy('month')
    ->get();

        $monthlyStats = array_fill(1, 12, 0);
        foreach ($monthlyPayments as $data) {
            $monthlyStats[$data->month] = $data->monthly_payment;
        }

        return collect($monthlyStats)->map(function ($amount, $month) {
            return [
                "month" => $this->translateMonthToFrench($month),
                "real_turnover" => $amount,
            ];
        })->values();
    }

    // public function getRecovery($year)
    // {
    //     $totalTurnover = $this->getTotalTurnover($year)->pluck('total_turnover', 'month')->toArray();
    //     $realTurnover = $this->getRealTurnover($year)->pluck('real_turnover', 'month')->toArray();

    //     $monthlyRecovery = [];
    //     foreach (range(1, 12) as $month) {
    //         $monthName = $this->translateMonthToFrench($month);
    //         $monthlyRecovery[] = [
    //             "month" => $monthName,
    //             "recovery" => ($totalTurnover[$monthName] ?? 0) - ($realTurnover[$monthName] ?? 0),
    //         ];
    //     }

    //     return $monthlyRecovery;
    // }


    public function getRecovery($year, $client)
    {
      $unpaidInvoices = Invoice::when($year, function ($query, $year) {
                return $query->whereYear('created_at', $year);
            })
            ->when($client, function ($query, $client) {
                return $query->where('client_id', $client);
            })
            ->where('status', 'Non payé')
            ->get()
            ->groupBy(function ($invoice) {
                return (int) \Carbon\Carbon::parse($invoice->created_at)->format('n');
            });

$unpaidOrderReceipts = OrderReceipt::when($year, function ($query, $year) {
                return $query->whereYear('created_at', $year);
            })
            ->when($client, function ($query, $client) {
                return $query->where('client_id', $client);
            })
            ->where('status', 'Non payé')
            ->get()
            ->groupBy(function ($receipt) {
                return (int) \Carbon\Carbon::parse($receipt->created_at)->format('n');
            });


        $monthlyRecovery = [];
        foreach (range(1, 12) as $month) {
            $monthName = $this->translateMonthToFrench($month);

            $invoices = $unpaidInvoices[$month] ?? collect();
            $orderReceipts = $unpaidOrderReceipts[$month] ?? collect();

            $totalRecovery = $invoices->sum('amount') + $orderReceipts->sum('amount');

            $monthlyRecovery[] = [
                "month" => $monthName,
                "recovery" => $totalRecovery,
            ];
        }

        return $monthlyRecovery;
    }

    private function translateMonthToFrench($month)
    {
        $monthsInFrench = [
            1 => 'Janvier',
            2 => 'Février',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Août',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre',
        ];

        return $monthsInFrench[$month];
    }



    public function getTotalTurnoverByClientType(?int $year = null, $client)
    {
   $result = ClientType::with(['clients.invoices' => function ($query) use ($year, $client) {
    $query->selectRaw('client_id, SUM(final_amount) as total_turnover')
        ->groupBy('client_id')
        ->when($year, function ($query, $year) {
            return $query->whereYear('created_at', $year);
        })
        ->when($client, function ($query, $client) {
            return $query->where('client_id', $client);
        });
}])->get();


        return $result->map(function ($clientType) {
            $totalTurnover = 0;

            foreach ($clientType->clients as $client) {
                foreach ($client->invoices as $invoice) {
                    $totalTurnover += $invoice->total_turnover;
                }
            }

            return [
                'client_type' => $clientType->name,
                'total_turnover' => $totalTurnover,
            ];
        });
    }

    public function sumPayments($invoiceId)

    {

        return Payment::where('invoice_id', $invoiceId)

            ->whereNull('deleted_at')

            ->sum('amount');
    }


    // public function getTopCitiesByTurnover(?int $year = null)
    // {
    //     $cities = City::with(['clients.invoices' => function ($query) use ($year) {
    //         if ($year) {
    //             $query->whereYear('created_at', $year)
    //                 ->selectRaw('client_id, SUM(final_amount) as total_turnover')
    //                 ->groupBy('client_id');
    //         }
    //     }])->get();

    //     $citiesWithTurnover = $cities->map(function ($city) {
    //         $totalTurnover = 0;


    //         foreach ($city->clients as $client) {
    //             foreach ($client->invoices as $invoice) {
    //                 $totalTurnover += $invoice->total_turnover;
    //             }
    //         }

    //         return [
    //             'city_name' => $city->name,
    //             'total_turnover' => $totalTurnover,
    //         ];
    //     });

    //     $sortedCities = $citiesWithTurnover->sortByDesc('total_turnover');

    //     $totalTurnover = $citiesWithTurnover->sum('total_turnover');

    //     $topCities = $sortedCities->take(6);

    //     $result = $topCities->map(function ($city) use ($totalTurnover) {
    //         $percentage = $totalTurnover > 0
    //             ? ($city['total_turnover'] / $totalTurnover) * 100
    //             : 0;

    //         return [
    //             'city_name' => $city['city_name'],
    //             'total_turnover' => $city['total_turnover'],
    //             'percentage_of_total' => round($percentage, 2),
    //         ];
    //     });

    //     return $result->values();
    // }
    public function getTopClientsByTurnover(?int $year = null, $client)
    {
       $clients = Client::with(['invoices' => function ($query) use ($year, $client) {
    $query->when($year, function ($query, $year) {
        return $query->whereYear('created_at', $year);
    })->when($client, function ($query, $client) {
        return $query->where('client_id', $client);
    });
}])->get();


        $clientsWithTurnover = [];
        foreach ($clients as $client) {
            $clientTotalTurnover = $client->invoices->sum('final_amount');

            $clientsWithTurnover[] = [
                'client_name' => $client->trade_name ?: $client->legal_name,
                'total_turnover' => $clientTotalTurnover,
                'client_total_turnover' => $clientTotalTurnover,
            ];
        }

        usort($clientsWithTurnover, function ($a, $b) {
            return $b['total_turnover'] <=> $a['total_turnover'];
        });

        $totalTurnover = array_sum(array_column($clientsWithTurnover, 'total_turnover'));

        $topFive = array_slice($clientsWithTurnover, 0, 5);
        $others = array_slice($clientsWithTurnover, 5);

        $othersTotal = array_sum(array_column($others, 'total_turnover'));

        $result = [];

        foreach ($topFive as $client) {
            $percentage = $totalTurnover > 0
                ? ($client['total_turnover'] / $totalTurnover) * 100
                : 0;

            $result[] = [
                'client_name' => $client['client_name'],
                'total_turnover' => $client['total_turnover'],
                'percentage_of_total' => round($percentage, 2),
                'client_total_turnover' => $client['client_total_turnover'],
            ];
        }

        if ($othersTotal > 0) {
            $result[] = [
                'client_name' => 'Autres',
                'total_turnover' => $othersTotal,
                'percentage_of_total' => round(($othersTotal / $totalTurnover) * 100, 2),
                'client_total_turnover' => $othersTotal,
            ];
        }

        return collect($result)->values();
    }


    public function getTopActivitySectorsByTurnover(?int $year = null, $client)
    {
       $sectors = BusinessSector::with(['clients.invoices' => function ($query) use ($year, $client) {
    $query->when($client, fn($q) => $q->where('client_id', $client))
          ->when($year, fn($q) => $q->whereYear('created_at', $year));
}])->get();


        $sectorsWithTurnover = [];

        foreach ($sectors as $sector) {
            $totalTurnover = 0;

            foreach ($sector->clients as $client) {
                foreach ($client->invoices as $invoice) {
                    $totalTurnover += $invoice->final_amount;
                }
            }

            $sectorsWithTurnover[] = [
                'sector_name' => $sector->name,
                'total_turnover' => $totalTurnover,
            ];
        }

        usort($sectorsWithTurnover, function ($a, $b) {
            return $b['total_turnover'] <=> $a['total_turnover'];
        });

        $totalTurnover = array_sum(array_column($sectorsWithTurnover, 'total_turnover'));

        $topFive = array_slice($sectorsWithTurnover, 0, 5);
        $others = array_slice($sectorsWithTurnover, 5);

        $othersTotal = array_sum(array_column($others, 'total_turnover'));

        $result = [];

        foreach ($topFive as $sector) {
            $percentage = $totalTurnover > 0
                ? ($sector['total_turnover'] / $totalTurnover) * 100
                : 0;

            $result[] = [
                'sector_name' => $sector['sector_name'],
                'total_turnover' => $sector['total_turnover'],
                'percentage_of_total' => round($percentage, 2),
            ];
        }

        $result[] = [
            'sector_name' => 'Autres',
            'total_turnover' => $othersTotal,
            'percentage_of_total' => $totalTurnover > 0
                ? round(($othersTotal / $totalTurnover) * 100, 2)
                : 0,
        ];

        return collect($result)->values();
    }

    public function getQuery()
    {
        return Payment::query();
    }
}
