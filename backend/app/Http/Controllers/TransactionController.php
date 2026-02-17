<?php

namespace App\Http\Controllers;

use App\Exports\TransactionExport;
use App\Helpers\FilterHelper;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\CashTransactionType;
use App\Models\Client;
use App\Models\UserCashRegister;
use App\Repositories\Contracts\CashRegisterDailyBalancesRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class TransactionController extends Controller
{
    protected $repository;
    protected $dailyBalanceRepository;

    public function __construct(
        TransactionRepositoryInterface $repository,
        CashRegisterDailyBalancesRepositoryInterface $dailyBalanceRepository
    ) {
        $this->repository = $repository;
        $this->dailyBalanceRepository = $dailyBalanceRepository;
    }


    private function validateUserAccessToCashRegister($cashRegisterId)
    {
        $authUser = Auth::user();
        $cashRegistercheck = UserCashRegister::where('cash_register_id', $cashRegisterId)
            ->where('user_id', $authUser->id)
            ->first();
        $cashRegister = CashRegister::find($cashRegisterId);
        if (!$cashRegistercheck || $cashRegistercheck->user_id !== $authUser->id) {
            throw new Exception('Accès non autorisé à la caisse.', 403);
        }

        return $cashRegister;
    }


    public function createTransaction(Request $request)
    {
        try {
            date_default_timezone_set('Africa/Casablanca');

            $data = $request->validate(
                [
                    'cash_register_id' => 'required|uuid|exists:cash_registers,id',
                    'cash_transaction_type_id' => 'required|uuid|exists:cash_transaction_types,id',
                    'amount' => 'required|numeric|min:0',
                    'comment' => 'nullable|string',
                    'date' => 'required',
                    'client_id' => 'nullable|uuid|exists:clients,id',
                    'target_user_id' => 'nullable|uuid|exists:users,id',
                    'target_cash_register_id' => 'nullable|uuid|exists:cash_registers,id',
                    'balance_reset' => 'nullable|boolean',
                    'seller' => 'nullable|string',
                    'bank' => 'nullable|string',
                ],
            );

            $authUser = Auth::user();
            $isCashRegisterManager = $authUser->roles->contains('name', 'cashregister_manager');
            $isTransactionManager = $authUser->roles->contains('name', 'transaction_manager');

            if (!$isCashRegisterManager && !$isTransactionManager) {
                throw new Exception('Vous n\'avez pas les permissions nécessaires pour créer cette transaction.');
            }

            try {
                $transactionDate = \Carbon\Carbon::createFromFormat('d/m/Y', $data['date'], 'Africa/Casablanca')->startOfDay();
            } catch (\Exception $e) {
                throw new Exception('Format de date invalide. Utilisez le format: jj/mm/aaaa (ex: 03/12/2025)');
            }

            $today = \Carbon\Carbon::now('Africa/Casablanca')->startOfDay();

            if (!$transactionDate->isSameDay($today)) {
                throw new Exception('Vous ne pouvez créer des transactions que pour la date actuelle (' . $today->format('d/m/Y') . '). Date reçue: ' . $transactionDate->format('d/m/Y'));
            }

            $data['date'] = $transactionDate->format('Y-m-d');
            if (empty($data['cash_register_id'])) {
                throw new Exception('L\'ID de la caisse est requis.');
            }

            $cashRegister = $this->validateUserAccessToCashRegister($data['cash_register_id']);

            $cashRegister = CashRegister::find($data['cash_register_id']);
            if (!$cashRegister) {
                throw new Exception('Invalid cash register ID.');
            }

            $adjustment = null;

            $data['user_id'] = $authUser->id;

            if (empty($data['name'])) {
                if (!empty($data['client_id'])) {
                    $client = Client::find($data['client_id']);
                    if ($client) {
                        $data['name'] = $client->name;
                    }
                } elseif (!empty($data['target_user_id'])) {
                    $targetUser = \App\Models\User::find($data['target_user_id']);
                    if ($targetUser) {
                        $data['name'] = $targetUser->name;
                    }
                } elseif (!empty($data['target_cash_register_id'])) {
                    $targetCashRegister = CashRegister::find($data['target_cash_register_id']);
                    if ($targetCashRegister) {
                        $data['name'] = $targetCashRegister->name;
                    }
                } else {
                    $data['name'] = $authUser->name;
                }
            }

            if (isset($data['client_id']) && $data['client_id'] === '') {
                $data['client_id'] = null;
            }

            if (isset($data['target_user_id']) && $data['target_user_id'] === '') {
                $data['target_user_id'] = null;
            }

            if (isset($data['target_cash_register_id']) && $data['target_cash_register_id'] === '') {
                $data['target_cash_register_id'] = null;
            }

            $data['balance_reset'] = (int) ($request->balance_reset ?? 0);

            if (!empty($data['balance_reset']) && $data['balance_reset'] == 1) {
                $transactionType = CashTransactionType::where('name', 'out')->first();
                if (!$transactionType) {
                    throw new Exception('Le type de transaction "out" n\'est pas défini.');
                }

                $data['amount'] = $cashRegister->balance;
                $data['cash_transaction_type_id'] = $transactionType->id;
                $cashRegister->balance = 0;
                $cashRegister->save();

                $transaction = $this->repository->createTransaction($data);
                if (!$transaction) {
                    throw new Exception('Échec de la transaction.');
                }

                return response()->json([
                    'status' => 201,
                    'message' => 'Transaction créée avec succès, solde réinitialisé.',
                    'id' => $transaction->id,
                    'code' => $transaction->code
                ], 201);
            }

            if (empty($data['cash_transaction_type_id'])) {
                throw new Exception("L'ID du type de transaction est requis.");
            }

            $transactionType = CashTransactionType::find($data['cash_transaction_type_id']);
            if (!$transactionType) {
                throw new Exception('Type de transaction invalide.');
            }

            // Validate balance for regular transactions (no target cash register)
            if (empty($data['target_cash_register_id'])) {
                // Check if it's an outgoing transaction (sign = -1)
                if ($transactionType->sign == -1) {
                    if ($cashRegister->balance < $data['amount']) {
                        throw new Exception("Solde insuffisant dans la caisse. Solde actuel: {$cashRegister->balance}, Montant demandé: {$data['amount']}");
                    }
                }

                $adjustment = $data['amount'] * $transactionType->sign;
                $cashRegister->balance += $adjustment;
                $cashRegister->save();
            }

            $inflows = ($transactionType->sign == 1) ? $data['amount'] : 0;
            $outflows = ($transactionType->sign == -1) ? $data['amount'] : 0;

            $cashRegisterId = $cashRegister->id;

            // Handle transfers between cash registers
            if (!empty($data['target_cash_register_id'])) {
                $targetCashRegister = CashRegister::find($data['target_cash_register_id']);

                if (!$targetCashRegister) {
                    throw new Exception('Caisse cible introuvable.');
                }

                if ($transactionType->sign === 1) {
                    // Money coming into current cash register from target cash register
                    if ($targetCashRegister->balance < $data['amount']) {
                        throw new Exception("Solde insuffisant dans la caisse source. Solde actuel: {$targetCashRegister->balance}, Montant demandé: {$data['amount']}");
                    }
                    $adjustment = $data['amount'] * $transactionType->sign;
                    $cashRegister->balance += $adjustment;
                    $targetCashRegister->balance -= $data['amount']; // Deduct from source
                    $cashRegister->save();
                    $targetCashRegister->save();
                    $cashRegisterId = $cashRegister->id;
                }

                if ($transactionType->sign === -1) {
                    // Money going out from current cash register to target cash register
                    if ($cashRegister->balance < $data['amount']) {
                        throw new Exception("Solde insuffisant dans la caisse source. Solde actuel: {$cashRegister->balance}, Montant demandé: {$data['amount']}");
                    }
                    $adjustment = $data['amount'] * $transactionType->sign;
                    $cashRegister->balance += $adjustment; // This will subtract since sign is -1
                    $targetCashRegister->balance += $data['amount']; // Add to target
                    $cashRegister->save();
                    $targetCashRegister->save();
                    $cashRegisterId = $cashRegister->id;
                }

                $data['name'] = $targetCashRegister->name;
            }

         

            if (!empty($data['client_id'])) {
                $client = Client::find($data['client_id']);
                if ($client) {
                    if ($transactionType->sign == 1) {
                        $client->balance += $data['amount'];
                        $client->save();
                    }
                }
            }

            $transaction = $this->repository->createTransaction($data);
            
               $this->dailyBalanceRepository->updateOrCreateDailyBalance(
                $cashRegisterId,
                $adjustment,
                $inflows,
                $outflows
            );
            if (!$transaction) {
                throw new Exception('Échec de la création de la transaction.');
            }

            return response()->json([
                'status' => 201,
                'message' => 'Transaction créée avec succès',
                'id' => $transaction->id,
                'code' => $transaction->code
            ], 201);
        } catch (Exception $e) {
            Log::error('Transaction Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
                'error' => 'Erreur lors de la création de la transaction'
            ], 500);
        }
    }

    public function updateTransaction($id, Request $request)
    {
        try {
            $data = $request->validate([
                'amount' => 'integer',
                'date' => 'string',
                'cash_register_id' => 'required|uuid|exists:cash_registers,id',
                'cash_transaction_type_id' => 'required|uuid|exists:cash_transaction_types,id',
                'comment' => 'nullable|string',
                'seller' => 'nullable|string',
                'bank' => 'nullable|string',

            ]);

            $authUser = Auth::user();
            $data['user_id'] = $authUser->id;
            $data['balance_reset'] = (int) ($request->balance_reset ?? 0);

            $existingTransaction = CashTransaction::find($id);
            if (!$existingTransaction) {
                throw new Exception('Transaction non trouvée.');
            }

            $cashRegister = $this->validateUserAccessToCashRegister($data['cash_register_id']);

            $isCashRegisterManager = $authUser->roles->contains('name', 'cashregister_manager');
            $isTransactionManager = $authUser->roles->contains('name', 'transaction_manager');



            if (!$isCashRegisterManager && !$isTransactionManager) {
                throw new Exception('Vous n\'avez pas les permissions nécessaires pour modifier cette transaction.');
            }

            $existingDate = \Carbon\Carbon::parse($existingTransaction->date)->format('Y-m-d');
            $today = \Carbon\Carbon::today()->format('Y-m-d');

            if ($existingDate !== $today) {
                throw new Exception('Vous ne pouvez modifier que les transactions de la date actuelle.');
            }

            $data['date'] = $existingDate;

            $transactionType = CashTransactionType::find($data['cash_transaction_type_id']);
            if (!$transactionType) {
                throw new Exception('Type de transaction invalide.');
            }

            $oldTransactionType = CashTransactionType::find($existingTransaction->cash_transaction_type_id);
            $oldAdjustment = $existingTransaction->amount * $oldTransactionType->sign;
            $newAdjustment = $data['amount'] * $transactionType->sign;
            $balanceDifference = $newAdjustment - $oldAdjustment;

            $cashRegister->balance += $balanceDifference;
            $cashRegister->save();

            $inflows = ($transactionType->sign == 1) ? $data['amount'] : 0;
            $outflows = ($transactionType->sign == -1) ? $data['amount'] : 0;
            $oldInflows = ($oldTransactionType->sign == 1) ? $existingTransaction->amount : 0;
            $oldOutflows = ($oldTransactionType->sign == -1) ? $existingTransaction->amount : 0;

            $inflowsDifference = $inflows - $oldInflows;
            $outflowsDifference = $outflows - $oldOutflows;

            $this->dailyBalanceRepository->updateOrCreateDailyBalance(
                $cashRegister->id,
                $balanceDifference,
                $inflowsDifference,
                $outflowsDifference
            );

            $transaction = $this->repository->updateTransaction($id, $data);

            return response()->json([
                'status' => 200,
                'message' => 'Transaction mise à jour avec succès.',
                'id' => $transaction->id
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function getTransactionByIdSimple($id)
    {
        try {
            if (!is_string($id) || empty($id)) {
                throw new Exception('ID de transaction invalide.');
            }

            $transaction = $this->repository->findTransactionById($id);

            if (!$transaction) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Transaction introuvable.',
                ], 404);
            }

            // Check user access
            $this->validateUserAccessToCashRegister($transaction->cash_register_id);

            return response()->json([
                'status' => 200,
                'message' => 'Transaction récupérée avec succès.',
                'data' => $transaction
            ], 200);
        } catch (Exception $e) {
            Log::error('Get Transaction Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Erreur lors de la récupération de la transaction.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function softDeleteTransaction($id)
    {
        try {
            $authUser = Auth::user();
            $isCashRegisterManager = $authUser->roles->contains('name', 'cashregister_manager');
            $isTransactionManager = $authUser->roles->contains('name', 'transaction_manager');

            if (!$isCashRegisterManager && !$isTransactionManager) {
                throw new Exception('Vous n\'avez pas les permissions nécessaires pour supprimer cette transaction.');
            }

            $transaction = $this->repository->findTransactionById($id);
            if (!$transaction) {
                throw new Exception('Transaction introuvable.');
            }

            $cashRegister = CashRegister::find($transaction->cash_register_id);
            $transactionType = CashTransactionType::find($transaction->cash_transaction_type_id);

            if (!$cashRegister || !$transactionType) {
                throw new Exception('Caisse ou type de transaction introuvable.');
            }

            $transactionDate = \Carbon\Carbon::parse($transaction->date)->format('Y-m-d');
            $today = \Carbon\Carbon::now('Africa/Casablanca')->format('Y-m-d');

            if ($transactionDate !== $today) {
                throw new Exception('Vous ne pouvez supprimer que les transactions de la date actuelle.');
            }

            if (empty($transaction->target_cash_register_id)) {
                $adjustment = $transaction->amount * $transactionType->sign;
                $cashRegister->balance -= $adjustment;
                $cashRegister->save();
            } else {
                $targetCashRegister = CashRegister::find($transaction->target_cash_register_id);
                if (!$targetCashRegister) {
                    throw new Exception('Caisse cible introuvable.');
                }

                if ($transactionType->sign === 1) {
                    $cashRegister->balance -= $transaction->amount;
                    $targetCashRegister->balance += $transaction->amount;
                    $cashRegister->save();
                    $targetCashRegister->save();
                } else {
                    $cashRegister->balance += $transaction->amount;
                    $targetCashRegister->balance -= $transaction->amount;
                    $cashRegister->save();
                    $targetCashRegister->save();
                }
            }

            $inflows = ($transactionType->sign == 1) ? $transaction->amount : 0;
            $outflows = ($transactionType->sign == -1) ? $transaction->amount : 0;

            $cashRegisterId = $cashRegister->id;
            $reversalAdjustment = - ($transaction->amount * $transactionType->sign);

            if (!empty($transaction->target_cash_register_id)) {
                if ($transactionType->sign === 1) {
                    $cashRegisterId = $cashRegister->id;
                } else {
                    $cashRegisterId = $transaction->target_cash_register_id;
                    $reversalAdjustment = $transaction->amount * $transactionType->sign;
                }
            }

            $this->dailyBalanceRepository->updateOrCreateDailyBalance(
                $cashRegisterId,
                $reversalAdjustment,
                -$inflows,
                -$outflows
            );

            if (!empty($transaction->client_id)) {
                $client = Client::find($transaction->client_id);
                if ($client && $transactionType->sign == 1) {
                    $client->balance -= $transaction->amount;
                    $client->save();
                }
            }

            if ($transaction->balance_reset == 1) {
                throw new Exception('La suppression des transactions de réinitialisation de solde nécessite une logique spéciale.');
            }

            $this->repository->softDeleteTransaction($id);

            return response()->json([
                'status' => 200,
                'message' => 'Transaction supprimée avec recalcul des soldes.',
            ]);
        } catch (Exception $e) {
            Log::error('Soft Delete Transaction Error: ' . $e->getMessage());

            throw $e;
        }
    }

    public function getAll(Request $request)
    {
        try {
            $authUser = Auth::user();
            $filters = [
                'cash_register_id' => $request->input('cash'),
                'cash_transaction_type_id' => $request->input('transaction_type'),
                'search' => $request->input('search'),
                'date_range' => $request->input('range'),
                'user_id'  => $request->input('user'),
                'archive' => $request->input('archive'),
            ];

            $perPage = $request->input('per_page', 10);
            $userCashRegisters = UserCashRegister::where('user_id', $authUser->id)->pluck('cash_register_id');

            $query = CashTransaction::with(['transactionType:id,name', 'cashRegister:id,name', 'user:id,full_name,photo', 'client:id,legal_name', 'targetCashRegister:id,name', 'targetUser:id,full_name,photo'])
                ->whereIn('cash_register_id', $userCashRegisters);

            if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
                $query->onlyTrashed();
            }

            $query = FilterHelper::applyFilters($query, $filters, ['cash_register_id', 'cash_transaction_type_id', 'user_id']);

            // Only apply date filter if range is provided and not empty
            if (!empty($filters['date_range']) && $filters['date_range'] !== null && $filters['date_range'] !== '') {
                $dateRange = explode(' au ', $filters['date_range']);
                if (count($dateRange) === 2) {
                    try {
                        $debutDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dateRange[0]))->format('Y-m-d');
                        $finDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dateRange[1]))->format('Y-m-d');
                        $query->whereBetween(DB::raw('DATE(date)'), [$debutDate, $finDate]);
                    } catch (\Exception $e) {
                        // If date parsing fails, skip the date filter
                        Log::warning('Invalid date range format: ' . $filters['date_range']);
                    }
                }
            }

            if (!empty($filters['search'])) {
                $searchQuery = $filters['search'];
                $query->where(function ($q) use ($searchQuery) {
                    $q->whereHas('user', function ($userQuery) use ($searchQuery) {
                        $userQuery->where('first_name', 'LIKE', "%$searchQuery%")
                            ->orWhere('last_name', 'LIKE', "%$searchQuery%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$searchQuery%"]);
                    })->orWhere('name', 'LIKE', "%$searchQuery%");
                });
            }


             $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
            $transactions = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'current_page' => $transactions->currentPage(),
                'total_transactions' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'transactions' => $transactions->items(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Erreur lors de la récupération des transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTransactionById(Request $request, $id)
    {
        $isArchived = filter_var($request->query('archive'), FILTER_VALIDATE_BOOLEAN);

        $transaction = $isArchived
            ? CashTransaction::onlyTrashed()->with(['transactionType', 'cashRegister', 'targetCashRegister', 'user:id,full_name,photo', 'client'])->find($id)
            : CashTransaction::with(['transactionType', 'cashRegister', 'targetCashRegister', 'user:id,full_name,photo', 'client'])->find($id);

        if ($transaction) {
            return response()->json(array_merge(
                $transaction->toArray(),
                [
                    'client' => $transaction->client ? $transaction->client->legal_name : 'Inconnue',
                    'target_user' => $transaction->user ? $transaction->user->full_name : 'Inconnue',
                    'transaction_type_sign' => $transaction->transactionType ? $transaction->transactionType->sign : null,
                    'target_cash_register' => $transaction->targetCashRegister ? $transaction->targetCashRegister->name : 'Inconnue',
                    'cash_transaction_type' => $transaction->transactionType ? $transaction->transactionType->name : 'Inconnue',
                    'cash_register' => $transaction->cashRegister ? $transaction->cashRegister->name : 'Inconnue',
                ]
            ), 200);
        }

        return response()->json(['message' => 'Utilisateur introuvable'], 404);
    }

    public function exportTransactions(Request $request)
    {
        try {
            $filters = [
                'cash_register_id' => $request->input('cash'),
                'cash_transaction_type_id' => $request->input('transaction_type'),
                'search' => $request->input('search'),
                'date_range' => $request->input('range'),
                'user_id' => $request->input('user'),
            ];

            $perPage = $request->input('per_page', 10);

            $fileName = 'transactions_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

            $export = new TransactionExport($filters, $perPage);

            Excel::store($export, 'public/' . $fileName);

            $downloadUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'Transactions exportées avec succès.',
                'download_url' => $downloadUrl,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Échec de l\'exportation des transactions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
