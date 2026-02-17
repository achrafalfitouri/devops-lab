<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\CashRegisterRepositoryInterface;
use App\Repositories\Contracts\CashRegisterDailyBalancesRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\CashRegister;
use App\Models\CashRegisterDailyBalances;
use App\Models\CashTransaction;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCashRegister;
use Carbon\Carbon;


use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends Controller
{
    protected $repository;
    protected $dailyBalanceRepository;

    public function __construct(CashRegisterRepositoryInterface $repository, CashRegisterDailyBalancesRepositoryInterface $dailyBalanceRepository)
    {
        $this->repository = $repository;
        $this->dailyBalanceRepository = $dailyBalanceRepository;
    }

    public function createCashRegister(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'balance' => 'nullable|numeric'
        ]);

        $data['balance'] = $data['balance'] ?? 0;
        $data = array_map(function ($value) {
            return empty($value) && $value !== 0 ? null : $value;
        }, $data);
        $cashRegister = $this->repository->createCashRegister($data);

        $this->dailyBalanceRepository->storeBalance($cashRegister->id, $cashRegister->balance);

        return response()->json([
            'status' => 201,
            'message' => 'Caisse créée avec succès',
            'id' => $cashRegister->id
        ], 201);
    }



    public function updateCashRegister($id, Request $request)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'balance' => 'sometimes|integer',
        ]);
        $data = array_map(function ($value) {
            return empty($value) && $value !== 0 ? null : $value;
        }, $data);
        $cashRegister = $this->repository->updateCashRegister($id, $data);

        $this->dailyBalanceRepository->updateBalance($cashRegister->id, $cashRegister->balance);

        return response()->json([
            'status' => 200,
            'message' => 'Caisse mise à jour avec succès',
            'id' => $cashRegister->id
        ]);
    }


    public function updateStatus(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'status' => 'required',
            ]);

            $cashregister = $this->repository->findById($id);

            if (!$cashregister) {
                return response()->json(['message' => 'Caisse introuvable'], 404);
            }

            $status = $request->input('status') === 'active' ? 1 : 0;

            $updated = $this->repository->updateCashRegister($id, $data);

            if (!$updated) {
                return response()->json(['message' => 'Échec de la mise à jour du statut'], 500);
            }

            $updatedCashRegister = $this->repository->findById($id);

            $responseCashRegister = [
                'id' => $updatedCashRegister->id,
                'status' => $updatedCashRegister->status,
            ];

            return response()->json([
                'message' => 'Statut mis à jour avec succès',
                'cashregister' => $responseCashRegister,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Une erreur s'est produite lors de la mise à jour du statut",
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function softDeleteCashRegister($id)
    {
        try {
            $this->repository->softDeleteCashRegister($id);

            return response()->json([
                'status' => 200,
                'message' => 'Caisse supprimée avec succès',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Erreur lors de la suppression de la caisse',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getAll(Request $request)
    {
        try {
            $filters = [
                'cash_register_id' => $request->input('cash'),
                'date_range' => $request->input('date_range'),
                'archive' => $request->input('archive'),
            ];

            $perPage = $request->input('per_page', 10);

            // Parse date range
            $startDate = null;
            $endDate = null;
            if (!empty($filters['date_range'])) {
                $dateRange = explode(' au ', $filters['date_range']);
                if (count($dateRange) === 2) {
                    try {
                        // Try to parse as DD/MM/YYYY format first
                        $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dateRange[0]))->startOfDay();
                        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dateRange[1]))->endOfDay();
                    } catch (\Exception $e) {
                        // Fallback to default Carbon parsing
                        try {
                            $startDate = \Carbon\Carbon::parse(trim($dateRange[0]))->startOfDay();
                            $endDate = \Carbon\Carbon::parse(trim($dateRange[1]))->endOfDay();
                        } catch (\Exception $e) {
                            // If both fail, use default date range
                            $startDate = null;
                            $endDate = null;
                        }
                    }
                }
            }

            if (!$startDate || !$endDate) {
                $startDate = \Carbon\Carbon::now()->subDays(6)->startOfDay();
                $endDate = \Carbon\Carbon::now()->endOfDay();
            }

            $authUser = Auth::user();

            // Check if user has cashregister_manager role
            $hasCashRegisterManagerRole = false;
            $userRoleIds = DB::table('role_users')->where('user_id', $authUser->id)->pluck('role_id');

            if ($userRoleIds->isNotEmpty()) {
                $hasCashRegisterManagerRole = Role::whereIn('id', $userRoleIds)
                    ->where('name', 'cashregister_manager')
                    ->exists();
            }

            // Get user's assigned cash registers
            $userCashRegisters = UserCashRegister::where('user_id', $authUser->id)->pluck('cash_register_id');

            // Base query for cash registers
            $baseQuery = CashRegister::query()
                ->with(['usercash', 'transactions', 'managed_by:id,full_name'])
                ->withCount(['transactions' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }]);

            // Filter by user's assigned cash registers if user is NOT a cashregister_manager
            if (!$hasCashRegisterManagerRole) {
                $baseQuery->whereIn('id', $userCashRegisters);
            }

            if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
                $baseQuery->onlyTrashed();
            }

            if (!empty($filters['cash_register_id'])) {
                $baseQuery->where('id', $filters['cash_register_id']);
            }

            // Get all cash registers for the dropdown/list (without date filter)
            $returnedcashregister = clone $baseQuery;
            $returnedcashregister = $returnedcashregister->get();

            // Get paginated cash registers with date filter (only if dates are available)
            $cashRegistersQuery = clone $baseQuery;
            if ($startDate && $endDate) {
                $cashRegistersQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $cashRegisters = $cashRegistersQuery->paginate($perPage);

            // Calculate total from cash registers based on user role
            $cashRegistersTotalQuery = CashRegister::query();

            // If not manager, filter by user's assigned cash registers
            if (!$hasCashRegisterManagerRole) {
                $cashRegistersTotalQuery->whereIn('id', $userCashRegisters);
            }

            // Apply archive filter if needed
            if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
                $cashRegistersTotalQuery->onlyTrashed();
            }

            // Apply specific cash register filter if provided
            if (!empty($filters['cash_register_id'])) {
                $cashRegistersTotalQuery->where('id', $filters['cash_register_id']);
            }

            $cashRegistersTotal = $cashRegistersTotalQuery->sum('balance');

            $cashRegisterId = $filters['cash_register_id'];

            // Build base query for transactions with cash register filter
            $transactionsBaseQuery = CashTransaction::query();
            if (!$hasCashRegisterManagerRole) {
                $transactionsBaseQuery->whereIn('cash_register_id', $userCashRegisters);
            }

            $outflows = (clone $transactionsBaseQuery)->when($cashRegisterId, function ($q) use ($cashRegisterId, $startDate, $endDate) {
                return $q->where('cash_register_id', $cashRegisterId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->whereHas('transactionType', function ($q) {
                        $q->where('sign', -1);
                    });
            }, function ($q) use ($startDate, $endDate) {
                return $q->whereBetween('created_at', [$startDate, $endDate])
                    ->whereHas('transactionType', function ($q) {
                        $q->where('sign', -1);
                    });
            })
                ->sum('amount');

            $inflows = (clone $transactionsBaseQuery)->when($cashRegisterId, function ($q) use ($cashRegisterId, $startDate, $endDate) {
                return $q->where('cash_register_id', $cashRegisterId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->whereHas('transactionType', function ($q) {
                        $q->where('sign', 1);
                    });
            }, function ($q) use ($startDate, $endDate) {
                return $q->whereBetween('created_at', [$startDate, $endDate])
                    ->whereHas('transactionType', function ($q) {
                        $q->where('sign', 1);
                    });
            })
                ->sum('amount');

            // Calculate balance as inflows - outflows for the date range
            $calculatedBalance = abs($inflows - $outflows);

            date_default_timezone_set('Africa/Casablanca');

            $dailyBalancesQuery = CashRegisterDailyBalances::with('cashRegister')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->when($cashRegisterId, function ($q) use ($cashRegisterId) {
                    return $q->where('cash_register_id', $cashRegisterId);
                });

            // Filter daily balances by user's cash registers if not manager
            if (!$hasCashRegisterManagerRole) {
                $dailyBalancesQuery->whereIn('cash_register_id', $userCashRegisters);
            }

            $dailyBalances = $dailyBalancesQuery->get();

            $dailyStats = $dailyBalances->groupBy(function ($dailyBalance) {
                return $dailyBalance->created_at->toDateString();
            })->map(function ($dailyBalancesForDate, $date) {
                $totalInflows = $dailyBalancesForDate->sum('inflows');
                $totalOutflows = $dailyBalancesForDate->sum('outflows');
                $balanceForDay = $dailyBalancesForDate->sum('balance');

                $firstBalance = $dailyBalancesForDate->first();

                return [
                    'date' => $firstBalance->created_at->toDateString(),
                    'day' => \Carbon\Carbon::parse($firstBalance->created_at)->format('l'),
                    'total_balance' => $balanceForDay,
                    'inflows' => $totalInflows,
                    'outflows' => $totalOutflows,
                ];
            });

            $daysCollection = collect();
            $rangeProvided = !empty($filters['date_range']) && $startDate && $endDate;

            if ($rangeProvided) {
                $iterDate = $startDate->copy();
                while ($iterDate->lte($endDate)) {
                    $dateString = $iterDate->toDateString();
                    $dayName = $iterDate->format('l');

                    $dailyStat = $dailyStats->firstWhere('date', $dateString);
                    if (!$dailyStat) {
                        $dailyStat = [
                            'date' => $dateString,
                            'day' => $dayName,
                            'total_balance' => 0,
                            'inflows' => 0,
                            'outflows' => 0,
                        ];
                    }
                    $daysCollection->push($dailyStat);
                    $iterDate->addDay();
                }
            } else {
                for ($i = 0; $i < 7; $i++) {
                    $date = \Carbon\Carbon::now()->subDays($i)->toDateString();
                    $day = \Carbon\Carbon::now()->subDays($i)->format('l');

                    $dailyStat = $dailyStats->firstWhere('date', $date);

                    if (!$dailyStat) {
                        $dailyStat = [
                            'date' => $date,
                            'day' => $day,
                            'total_balance' => 0,
                            'inflows' => 0,
                            'outflows' => 0,
                        ];
                    }

                    $daysCollection->push($dailyStat);
                }
            }

            // Total cash transactions count with user filter
            $totalTransactionsQuery = CashTransaction::whereBetween('created_at', [$startDate, $endDate]);
            if (!$hasCashRegisterManagerRole) {
                $totalTransactionsQuery->whereIn('cash_register_id', $userCashRegisters);
            }
            // Apply specific cash register filter if provided
            if (!empty($filters['cash_register_id'])) {
                $totalTransactionsQuery->where('cash_register_id', $filters['cash_register_id']);
            }

            return response()->json([
                'status' => 200,
                'total_cash_registers' => $cashRegisters->total(),
                'cash_registers' => $returnedcashregister,
                'total_cash_transactions' => $totalTransactionsQuery->count(),
                'stats' => [
                    'cashRegistersTotal' => abs($calculatedBalance),
                    'cashRegistersAllTimeTotal' => abs($cashRegistersTotal),
                    'inflows' => abs($inflows),
                    'outflows' => abs($outflows),
                ],
                'daily_stats' => $rangeProvided ? $daysCollection->values()->all() : array_reverse($daysCollection->values()->all())
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
}
