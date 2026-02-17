<?php

namespace App\Http\Controllers;

use App\Exports\PaymentExport;
use App\Helpers\FilterHelper;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\OrderReceipt;
use App\Models\PaymentType;
use App\Repositories\Contracts\PaymentLogRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\RecoveryLogRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paymentRepository;
    protected $paymentLogRepository;
    protected $recoveryLogRepository;
    protected $invoiceRepository;

    public function __construct(
        PaymentLogRepositoryInterface $paymentLogRepository,
        PaymentRepositoryInterface $paymentRepository,
        RecoveryLogRepositoryInterface $recoveryLogRepository,
        InvoiceRepositoryInterface $invoiceRepository
    ) {

        $this->paymentRepository = $paymentRepository;
        $this->paymentLogRepository = $paymentLogRepository;
        $this->recoveryLogRepository = $recoveryLogRepository;
        $this->invoiceRepository = $invoiceRepository;
    }

    public function getAll(Request $request, PaymentRepositoryInterface $repository)
    {
        try {
            $filters = [
                'client_id' => $request->input('client'),
                'date' => $request->input('date'),
                'payment_type_id' => $request->input('type'),
                'search' => $request->input('search'),
                'archive' => $request->input('archive'),
            ];

            $perPage = $request->input('per_page', 10);
            if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
                $query = $repository->getPayments()->onlyTrashed();
            } else {
                $query = $repository->getPayments()->with(['client', 'paymentType', 'invoice', 'orderReceipt']);
            }
            $query = FilterHelper::applyFilters($query, $filters, ['client_id', 'payment_type_id']);

            if (!empty($filters['date'])) {
                $dateRange = explode(' au ', $filters['date']);
                if (count($dateRange) === 2) {
                    try {
                        $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dateRange[0]))->startOfDay();
                        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dateRange[1]))->endOfDay();
                    } catch (\Exception $e) {
                        $startDate = \Carbon\Carbon::parse(trim($dateRange[0]))->startOfDay();
                        $endDate = \Carbon\Carbon::parse(trim($dateRange[1]))->endOfDay();
                    }
                    $query->whereBetween('date', [$startDate, $endDate]);
                }
            }

            $query = FilterHelper::applySearch($query, $filters['search'], ['code']);

            $query->orderBy('created_at', 'desc');
            $payments = $query->paginate($perPage);

            if ($payments->isEmpty()) {
                Log::info('No payments found', [
                    'filters' => $filters,
                    'query' => $query->toSql(),
                ]);
            }

            return response()->json([
                'status' => 200,
                'current_page' => $payments->currentPage(),
                'total_payments' => $payments->total(),
                'per_page' => $payments->perPage(),
                'payments' => $payments->items()
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des paiements : ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des paiements.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getAllRecoveries(Request $request, PaymentRepositoryInterface $repository)
    {
        try {
            $filters = [
                'client_id' => $request->input('client'),
                'date' => $request->input('date'),
                'payment_type_id' => $request->input('type'),
                'search' => $request->input('search'),
                'archive' => $request->input('archive'),
            ];

            $perPage = $request->input('per_page', 10);
            if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
                $query = $repository->getRecoveries()->onlyTrashed();
            } else {
                $query = $repository->getRecoveries()->with(['client', 'paymentType']);
            }
            $query = FilterHelper::applyFilters($query, $filters, ['client_id', 'payment_type_id']);

            if (!empty($filters['date'])) {
                $dateRange = explode(' au ', $filters['date']);
                if (count($dateRange) === 2) {
                    try {
                        $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dateRange[0]))->startOfDay();
                        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dateRange[1]))->endOfDay();
                    } catch (\Exception $e) {
                        $startDate = \Carbon\Carbon::parse(trim($dateRange[0]))->startOfDay();
                        $endDate = \Carbon\Carbon::parse(trim($dateRange[1]))->endOfDay();
                    }
                    $query->whereBetween('date', [$startDate, $endDate]);
                }
            }

            $query = FilterHelper::applySearch($query, $filters['search'], ['code']);

            $query->orderBy('created_at', 'desc');
            $payments = $query->paginate($perPage);

            if ($payments->isEmpty()) {
                Log::info('No payments found', [
                    'filters' => $filters,
                    'query' => $query->toSql(),
                ]);
            }

            return response()->json([
                'status' => 200,
                'current_page' => $payments->currentPage(),
                'total_recoveries' => $payments->total(),
                'per_page' => $payments->perPage(),
                'recoveries' => $payments->items()
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des recoveries : ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPaymentById(Request $request, $id)
    {
        $isArchived = filter_var($request->query('archive'), FILTER_VALIDATE_BOOLEAN);

        $payment = $isArchived
            ? $this->paymentRepository->getPayments()->onlyTrashed()->with(['client', 'paymentType', 'invoice', 'orderReceipt', 'recovery'])->find($id)
            : $this->paymentRepository->getPayments()->with(['client', 'paymentType', 'invoice', 'orderReceipt', 'recovery'])->find($id);

        if ($payment) {
            return response()->json(array_merge(
                $payment->toArray(),
                [
                    'payment_type' => $payment->paymentType->name ?? null,
                    'client_balance' => $payment->client->balance ?? 0,
                    'client' => $payment->client->legal_name ?? null,
                    'wire_transfer_number' => $payment->recovery->wire_transfer_number ?? null,
                    'check_number' => $payment->recovery->check_number ?? null,
                    'effect_number' => $payment->recovery->effect_number ?? null,

                ]
            ), 200);
        }

        return response()->json(['message' => 'Payment introuvable'], 404);
    }


    public function getRecoveryById(Request $request, $id)
    {
        $isArchived = filter_var($request->query('archive'), FILTER_VALIDATE_BOOLEAN);

        $recovery = $isArchived
            ? $this->paymentRepository->getRecoveryById($id)->onlyTrashed()->with(['client', 'paymentType'])->first()
            : $this->paymentRepository->getRecoveryById($id)->with(['client', 'paymentType'])->first();

        if ($recovery) {
            return response()->json(array_merge(
                $recovery->toArray(),
                [
                    'payment_type' => $recovery->paymentType->name ?? null,
                    'client_balance' => $recovery->client->balance ?? 0,
                    'client' => $recovery->client->legal_name ?? null,
                ]
            ), 200);
        }
        return response()->json(['message' => 'Payment introuvable'], 404);
    }




public function create(Request $request)
{
    $data = $request->validate([
        'date'                 => 'required|date_format:d/m/Y',
        'amount'               => 'required|numeric|min:0',
        'comment'              => 'nullable|string',
        'payment_type_id'      => 'required|uuid',
        'invoice_id'           => 'nullable|uuid',
        'order_receipt_id'     => 'nullable|uuid',
        'recovery_id'          => 'nullable|uuid',
        'check_number'         => 'nullable|string',
        'check_date'           => 'nullable|date_format:d/m/Y',
        'wire_transfer_number' => 'nullable|string',
        'effect_date'          => 'nullable|date_format:d/m/Y',
        'effect_number'        => 'nullable|string',
        'client_id'            => 'nullable|uuid',
    ]);

    // Parse dates after validation
    $date = !empty($data['date']) ? \Carbon\Carbon::createFromFormat('d/m/Y', $data['date'])->format('Y-m-d') : null;
    $checkDate = !empty($data['check_date']) ? \Carbon\Carbon::createFromFormat('d/m/Y', $data['check_date'])->format('Y-m-d') : null;
    $effectDate = !empty($data['effect_date']) ? \Carbon\Carbon::createFromFormat('d/m/Y', $data['effect_date'])->format('Y-m-d') : null;

    $paymentType = PaymentType::find($data['payment_type_id']);

    if (!empty($data['invoice_id'])) {
        $invoice = Invoice::find($data['invoice_id']);
        if (!$invoice) {
            return response()->json(['message' => 'Facture introuvable.'], 404);
        }
        $client = Client::find($invoice->client_id);
        if (!$client) {
            return response()->json(['message' => 'Client introuvable à partir de la facture.'], 404);
        }
        $data['client_id'] = $invoice->client_id;
    } elseif (!empty($data['order_receipt_id'])) {
        $orderReceipt = OrderReceipt::find($data['order_receipt_id']);
        if (!$orderReceipt) {
            return response()->json(['message' => 'Reçu de commande introuvable.'], 404);
        }
        $client = Client::find($orderReceipt->client_id);
        if (!$client) {
            return response()->json(['message' => 'Client introuvable à partir du reçu de commande.'], 404);
        }
        $data['client_id'] = $orderReceipt->client_id;
    } else {
        $client = Client::find($data['client_id']);
        if (!$client) {
            return response()->json(['message' => 'Client introuvable.'], 404);
        }
    }

    DB::beginTransaction();
    try {
        // If recovery_id is provided, validate it's for correct payment type
        if (!empty($data['recovery_id'])) {
            $recoveryTypes = ['Effet', 'Virement', 'Chèque', 'Cheque'];
            
            if (!in_array($paymentType->name, $recoveryTypes)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Le type de paiement doit être Effet, Virement ou Chèque pour utiliser un recouvrement.'
                ], 400);
            }
            
            // The repository will validate recovery exists and has sufficient balance
            // No need to do additional checks here
        }

        // Handle Espèce payment - validate amounts and update client balance
        if ($paymentType->name == 'Espèce') {
            if (!empty($data['invoice_id'])) {
                $payed_amount = $invoice->payed_amount ?? 0;
                // FIXED: Include tax in remaining amount calculation
                $total_invoice_amount = ($invoice->final_amount ?? 0) + ($invoice->tax_amount ?? 0);
                $remainingAmount = round($total_invoice_amount - $payed_amount, 2);

                if ($data['amount'] > $remainingAmount) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Le montant du paiement dépasse le montant restant de la facture.',
                        'remaining_amount' => $remainingAmount
                    ], 400);
                }
            } elseif (!empty($data['order_receipt_id'])) {
                $payed_amount = $orderReceipt->payed_amount ?? 0;
                $remainingAmount = round($orderReceipt->final_amount - $payed_amount, 2);

                if ($data['amount'] > $remainingAmount) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Le montant du paiement dépasse le montant restant du reçu de commande.',
                        'remaining_amount' => $remainingAmount
                    ], 400);
                }
            }

            if ($client->balance < $data['amount']) {
                DB::rollBack();
                return response()->json(['message' => 'Le montant du paiement dépasse le solde du client.'], 400);
            }
            $client->balance -= $data['amount'];
            $client->save();
        }

        $data = array_map(function ($value) {
            return empty($value) && $value !== 0 ? null : $value;
        }, $data);

        // Set the converted dates
        $data['date'] = $date;
        $data['check_date'] = $checkDate;
        $data['effect_date'] = $effectDate;

        $payment = $this->paymentRepository->create($data);

        // INVOICE PAYMENT PROCESSING - FIXED
        if (!empty($data['invoice_id'])) {
            $payed_amount = $invoice->payed_amount ?? 0;
            $final_amount = $invoice->final_amount ?? 0;
            $calculatedPayedAmount = $payed_amount + $data['amount'];

            $invoice->payed_amount = $calculatedPayedAmount;
            $invoice->total_to_pay = $final_amount - $calculatedPayedAmount;

            if (round($invoice->payed_amount, 2) >= round($final_amount, 2)) {
                $invoice->status = 'Payé';
                $invoice->total_to_pay = 0; // Ensure it's exactly 0 when fully paid
            } elseif (round($invoice->payed_amount, 2) > 0) {
                $invoice->status = 'Payé partiellement';
            } else {
                $invoice->status = 'Non payé';
            }

            $invoice->save();
        }

        // ORDER RECEIPT PAYMENT PROCESSING - FIXED
        if (!empty($data['order_receipt_id'])) {
            $payed_amount = $orderReceipt->payed_amount ?? 0;
            $final_amount = $orderReceipt->final_amount ?? 0; // FIXED: Use orderReceipt, not invoice
            $calculatedPayedAmount = $payed_amount + $data['amount'];

            $orderReceipt->payed_amount = $calculatedPayedAmount;
            $orderReceipt->total_to_pay = $final_amount - $calculatedPayedAmount; // FIXED: Use calculated amount

            if (round($orderReceipt->payed_amount, 2) >= round($orderReceipt->final_amount, 2)) {
                $orderReceipt->status = 'Payé';
                $orderReceipt->total_to_pay = 0;
            } elseif (round($orderReceipt->payed_amount, 2) > 0) {
                $orderReceipt->status = 'Payé partiellement';
            } else {
                $orderReceipt->status = 'Non payé';
            }
            $orderReceipt->save();
        }

        DB::commit();
        return response()->json($payment, 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Échec de la création du paiement.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function createRecovery(Request $request)
    {
        $data = $request->validate([
            'date'                 => 'required|date_format:d/m/Y',
            'amount'               => 'required|numeric|min:0',
            'comment'              => 'nullable|string',
            'payment_type_id'      => 'required|uuid',
            'check_number'         => 'nullable|string',
            'check_date'           => 'nullable|date_format:d/m/Y',
            'wire_transfer_number' => 'nullable|string',
            'effect_date'          => 'nullable|date_format:d/m/Y',
            'effect_number'        => 'nullable|string',
            'client_id'            => 'required|uuid',
        ]);

        // Parse dates after validation
        $date = !empty($data['date']) ? \Carbon\Carbon::createFromFormat('d/m/Y', $data['date'])->format('Y-m-d') : null;
        $checkDate = !empty($data['check_date']) ? \Carbon\Carbon::createFromFormat('d/m/Y', $data['check_date'])->format('Y-m-d') : null;
        $effectDate = !empty($data['effect_date']) ? \Carbon\Carbon::createFromFormat('d/m/Y', $data['effect_date'])->format('Y-m-d') : null;

        $client = Client::find($data['client_id']);
        if (!$client) {
            return response()->json(['message' => 'Client introuvable.'], 404);
        }

        $paymentType = PaymentType::find($data['payment_type_id']);
        if (!$paymentType) {
            return response()->json(['message' => 'Type de paiement introuvable.'], 404);
        }

        DB::beginTransaction();
        try {
            // Clean empty values
            $data = array_map(function ($value) {
                return empty($value) && $value !== 0 ? null : $value;
            }, $data);

            // Set the converted dates
            $data['date'] = $date;
            $data['check_date'] = $checkDate;
            $data['effect_date'] = $effectDate;

            $recovery = $this->paymentRepository->createRecovery($data);

            $this->recoveryLogRepository->createLog('create', [], $recovery->toArray(), Auth::user()->id, $recovery->id);

            DB::commit();
            return response()->json($recovery, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Échec de la création du recouvrement.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $payment = $this->paymentRepository->getById($id);

            if (!$payment) {
                throw new \Exception('Paiement introuvable.', 404);
            }

            $data = $request->validate([
                'date'                 => 'nullable|date_format:d/m/Y,Y-m-d\TH:i:s.u\Z',
                'amount'               => 'nullable|numeric|min:0',
                'comment'              => 'nullable|string',
                'payment_type_id'      => 'nullable|uuid',
                'invoice_id'           => 'nullable|uuid',
                'check_number'         => 'nullable|string',
                'check_date'           => 'nullable|date_format:d/m/Y,Y-m-d\TH:i:s.u\Z',
                'wire_transfer_number' => 'nullable|string',
                'effect_date'          => 'nullable|date_format:d/m/Y,Y-m-d\TH:i:s.u\Z',
                'effect_number'        => 'nullable|string',
                'code'                 => 'nullable|string',
                'recovery_id'          => 'nullable|uuid',
            ]);

            if (isset($data['code']) && $data['code'] === $payment->code) {
                unset($data['code']);
            } else if (isset($data['code'])) {
                $validator = Validator::make($data, [
                    'code' => 'unique:payments,code,' . $id,
                ]);

                if ($validator->fails()) {
                    return response()->json(['message' => 'Ce code est déjà utilisé par un autre document.'], 422);
                }
            }

            // Parse dates - handle both formats (d/m/Y from frontend edit, ISO from existing data)
            $date = null;
            if (!empty($data['date'])) {
                try {
                    $date = \Carbon\Carbon::createFromFormat('d/m/Y', $data['date'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $date = \Carbon\Carbon::parse($data['date'])->format('Y-m-d');
                }
            }

            $checkDate = null;
            if (!empty($data['check_date'])) {
                try {
                    $checkDate = \Carbon\Carbon::createFromFormat('d/m/Y', $data['check_date'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $checkDate = \Carbon\Carbon::parse($data['check_date'])->format('Y-m-d');
                }
            }

            $effectDate = null;
            if (!empty($data['effect_date'])) {
                try {
                    $effectDate = \Carbon\Carbon::createFromFormat('d/m/Y', $data['effect_date'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $effectDate = \Carbon\Carbon::parse($data['effect_date'])->format('Y-m-d');
                }
            }

            if (!empty($data['date'])) {
                $data['date'] = $date;
            }
            if (!empty($data['check_date'])) {
                $data['check_date'] = $checkDate;
            }
            if (!empty($data['effect_date'])) {
                $data['effect_date'] = $effectDate;
            }

            $oldPaymentData = $payment->toArray();
            $oldAmount = $payment->amount;
            $invoiceId = $data['invoice_id'] ?? $payment->invoice_id;

            DB::beginTransaction();

            if ($invoiceId) {
                $invoice = $this->invoiceRepository->findById($invoiceId);

                if (!$invoice) {
                    throw new \Exception('Invoice not found', 404);
                }

                $otherPaymentsTotal = $this->paymentRepository->sumPaymentsExcept($invoiceId, $id);
                $newAmount = $data['amount'] ?? $oldAmount;
                $newTotal = $otherPaymentsTotal + $newAmount;

                if ($newTotal > $invoice->final_amount) {
                    throw new \Exception('Le paiement mis à jour dépasse le montant final de la facture', 400);
                }
            }

            $data = array_map(function ($value) {
                return empty($value) && $value !== 0 ? null : $value;
            }, $data);

            $updated = $this->paymentRepository->update($id, $data);

            if (!$updated) {
                throw new \Exception('Échec de la mise à jour du paiement.', 500);
            }

            if (isset($invoice)) {
                $totalPayments = $this->paymentRepository->sumPayments($invoice->id);
                $invoice->status = match (true) {
                    round($totalPayments, 2) === round($invoice->final_amount, 2) => 'Payé',
                    $totalPayments > 0 => 'Payé partiellement',
                    default => 'Non payé',
                };
                $invoice->save();
            }

            $this->paymentLogRepository->createLog('update', $oldPaymentData, $updated->toArray(), Auth::user()->id, $id);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Paiement mis à jour avec succès.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            $statusCode = is_numeric($e->getCode()) && $e->getCode() >= 100 && $e->getCode() < 600 
                ? (int) $e->getCode() 
                : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function updateRecovery(Request $request, $id)
    {
        try {
            $recovery = $this->paymentRepository->getRecoveryById($id)->first();

            if (!$recovery) {
                throw new \Exception('Recouvrement introuvable.', 404);
            }

            $data = $request->validate([
                'date'                 => 'nullable|date_format:d/m/Y,Y-m-d\TH:i:s.u\Z',
                'amount'               => 'nullable|numeric|min:0',
                'comment'              => 'nullable|string',
                'payment_type_id'      => 'nullable|uuid',
                'check_number'         => 'nullable|string',
                'check_date'           => 'nullable|date_format:d/m/Y,Y-m-d\TH:i:s.u\Z',
                'wire_transfer_number' => 'nullable|string',
                'effect_date'          => 'nullable|date_format:d/m/Y,Y-m-d\TH:i:s.u\Z',
                'effect_number'        => 'nullable|string',
                'client_id'            => 'nullable|uuid',
                'code'                 => 'nullable|string',
            ]);

            if (isset($data['code']) && $data['code'] === $recovery->code) {
                unset($data['code']);
            } else if (isset($data['code'])) {
                $validator = Validator::make($data, [
                    'code' => 'unique:recoveries,code,' . $id,
                ]);

                if ($validator->fails()) {
                    return response()->json(['message' => 'Ce code est déjà utilisé par un autre recouvrement.'], 422);
                }
            }

            // Parse dates - handle both formats (d/m/Y from frontend edit, ISO from existing data)
            $date = null;
            if (!empty($data['date'])) {
                try {
                    $date = \Carbon\Carbon::createFromFormat('d/m/Y', $data['date'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $date = \Carbon\Carbon::parse($data['date'])->format('Y-m-d');
                }
            }

            $checkDate = null;
            if (!empty($data['check_date'])) {
                try {
                    $checkDate = \Carbon\Carbon::createFromFormat('d/m/Y', $data['check_date'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $checkDate = \Carbon\Carbon::parse($data['check_date'])->format('Y-m-d');
                }
            }

            $effectDate = null;
            if (!empty($data['effect_date'])) {
                try {
                    $effectDate = \Carbon\Carbon::createFromFormat('d/m/Y', $data['effect_date'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $effectDate = \Carbon\Carbon::parse($data['effect_date'])->format('Y-m-d');
                }
            }

            if (!empty($data['date'])) {
                $data['date'] = $date;
            }
            if (!empty($data['check_date'])) {
                $data['check_date'] = $checkDate;
            }
            if (!empty($data['effect_date'])) {
                $data['effect_date'] = $effectDate;
            }

            $oldRecoveryData = $recovery->toArray();

            DB::beginTransaction();

            $data = array_map(function ($value) {
                return empty($value) && $value !== 0 ? null : $value;
            }, $data);

            $updated = $this->paymentRepository->updateRecovery($id, $data);

            if (!$updated) {
                throw new \Exception('Échec de la mise à jour du recouvrement.', 500);
            }

            $this->recoveryLogRepository->createLog('update', $oldRecoveryData, $updated->toArray(), Auth::user()->id, $id);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Recouvrement mis à jour avec succès.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            $statusCode = is_numeric($e->getCode()) && $e->getCode() >= 100 && $e->getCode() < 600 
                ? (int) $e->getCode() 
                : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }



    public function exportPayments(Request $request)
    {
        try {
            $filters = [
                'comment' => $request->input('comment'),
                'date' => $request->input('date'),
                'search' => $request->input('search'),
            ];

            $perPage = $request->input('per_page', 10);

            $fileName = 'payments_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
            $filePath = storage_path('app/public/' . $fileName);

            $export = new PaymentExport($filters, $perPage);


            Excel::store($export, 'public/' . $fileName);

            $downloadUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'Paiements exportés avec succès.',
                'download_url' => $downloadUrl,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Échec de l exportation des paiements : ' . $e->getMessage(),
            ], 500);
        }
    }
    public function generatePdf($id)
    {
        try {

            $payment = $this->paymentRepository
                ->getPayments()
                ->with(['client', 'user', 'items', 'paymentType'])
                ->find($id);

            if (!$payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            }

            $paymentData = [
                'payment_id' => $payment->id,
                'client_name' => $payment->client->legal_name ?? 'N/A',
                'payment_date' => $payment->date,
                'payment_amount' => $payment->amount,
                'payment_type' => $payment->paymentType->name ?? 'N/A',
                'comment' => $payment->comment ?? 'N/A',
                'effect_date' => $payment->effect_date ?? 'N/A',
                'effect_number' => $payment->effect_number ?? 'N/A',
                'check_date' => $payment->check_date ?? 'N/A',
                'check_number' => $payment->check_number ?? 'N/A',
                'wire_transfer_number' => $payment->wire_transfer_number ?? 'N/A',
            ];

            $pdf = Pdf::loadView('pdf.payement', ['payment' => $paymentData]);

            $fileName = 'payment_' . $payment->id . '_' . time() . '.pdf';


            $pdf->save(storage_path('app/public/' . $fileName));

            $publicUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'PDF généré avec succès.',
                'download_url' => $publicUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la génération du PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function delete($id)
    {
        try {
            $deleted = $this->paymentRepository->delete($id);

            if ($deleted) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Paiement supprimé avec succès.'
                ], 200);
            }
            throw new \Exception('Paiement introuvable.', 404);
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function deleteRecovery($id)
    {
        try {
            $recovery = $this->paymentRepository->getRecoveryById($id)->first();
            
            if (!$recovery) {
                throw new \Exception('Recouvrement introuvable.', 404);
            }

            $oldRecoveryData = $recovery->toArray();
            
            $deleted = $this->paymentRepository->deleteRecovery($id);

            if ($deleted) {
                $this->recoveryLogRepository->createLog('delete', $oldRecoveryData, [], Auth::user()->id, $id);
                
                return response()->json([
                    'status' => 200,
                    'message' => 'Recouvrement supprimé avec succès.'
                ], 200);
            }
            throw new \Exception('Recouvrement introuvable.', 404);
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }
    public function getTotalTurnover(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $client = $request->input('client');

        $totalTurnover = $this->paymentRepository->getTotalTurnover($year, $client);
        return response()->json(['total_turnover' => $totalTurnover]);
    }
    public function getInvoiceTurnover(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $client = $request->input('client');

        $totalTurnover = $this->paymentRepository->getInvoiceTurnover($year, $client);
        return response()->json(['invoice_turnover' => $totalTurnover]);
    }
    public function getOrderReceiptTurnover(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $client = $request->input('client');
        $totalTurnover = $this->paymentRepository->getOrderReceiptTurnover($year, $client);
        return response()->json(['order_receipt_turnover' => $totalTurnover]);
    }

    public function getRealTurnover(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $client = $request->input('client');
        $realTurnover = $this->paymentRepository->getRealTurnover($year, $client);
        return response()->json(['real_turnover' => $realTurnover]);
    }

    public function getRecovery(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $client = $request->input('client');
        $recovery = $this->paymentRepository->getRecovery($year, $client);
        return response()->json(['recovery' => $recovery]);
    }


    public function getTurnoverData(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $client = $request->input('client');

        $result = [
            'by_client_type' => $this->paymentRepository->getTotalTurnoverByClientType($year, $client),
            // 'by_city' => $this->paymentRepository->getTopCitiesByTurnover($year),
            'by_sector' => $this->paymentRepository->getTopActivitySectorsByTurnover($year, $client),
            'by_client' => $this->paymentRepository->getTopClientsByTurnover($year, $client),
        ];

        return response()->json($result);
    }
}
