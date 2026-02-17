<?php

namespace App\Http\Controllers;

use App\Models\CashTransactionLogs;
use App\Models\ClientLog;
use App\Models\ContactLog;
use App\Models\DocumentLog;
use App\Models\ItemLog;
use App\Models\PaymentLog;
use App\Models\RecoveryLog;
use App\Models\UserLog;
use App\Repositories\Contracts\CashLogRepositoryInterface;
use App\Repositories\Contracts\ContactLogRepositoryInterface;
use App\Repositories\Contracts\ClientLogRepositoryInterface;
use App\Repositories\Contracts\PaymentLogRepositoryInterface;
use App\Repositories\Contracts\RecoveryLogRepositoryInterface;
use App\Repositories\Contracts\UserLogRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


class LogController extends Controller
{
    protected $contactLogRepository;
    protected $clientLogRepository;
    protected $paymentLogRepository;
    protected $recoveryLogRepository;
    protected $UserLogRepository;
    protected $cashLogRepository;

    public function __construct(
        ContactLogRepositoryInterface $contactLogRepository,
        ClientLogRepositoryInterface $clientLogRepository,
        PaymentLogRepositoryInterface $paymentLogRepository,
        RecoveryLogRepositoryInterface $recoveryLogRepository,
        UserLogRepositoryInterface $UserLogRepository,
        CashLogRepositoryInterface $cashLogRepository

    ) {
        $this->contactLogRepository = $contactLogRepository;
        $this->clientLogRepository = $clientLogRepository;
        $this->paymentLogRepository = $paymentLogRepository;
        $this->recoveryLogRepository = $recoveryLogRepository;
        $this->UserLogRepository = $UserLogRepository;
        $this->cashLogRepository = $cashLogRepository;
    }



    public function getUserLogs(Request $request)
    {
        try {
            $filters = [
                'action' => $request->input('action'),
                'search' => $request->input('search'),
            ];

            $perPage = $request->input('per_page', 10);

            $query = UserLog::with(['user:id,full_name']);

            $filterableFields = ['action'];


            if (!empty($filters['search'])) {
                $searchQuery = $filters['search'];
                $query->where(function ($q) use ($searchQuery) {
                    $q->whereHas('user', function ($userQuery) use ($searchQuery) {
                        $userQuery->where('first_name', 'LIKE', "%$searchQuery%")
                            ->orWhere('last_name', 'LIKE', "%$searchQuery%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$searchQuery%"]);
                    });
                });
            }

            foreach ($filterableFields as $field) {
                if (!empty($filters[$field])) {
                    $query->where($field, 'REGEXP', $filters[$field]);
                }
            }

            $query->orderBy('created_at', 'desc');
            $users = $query->paginate($perPage);

            if (!$users) {
                throw new \Exception('Utilisateur introuvable', 404);
            }

            return response()->json([
                'status' => 200,
                'current_page' => $users->currentPage(),
                'total_user_logs' => $users->total(),
                'per_page' => $users->perPage(),
                'user_logs' => $users->items(),
            ], 200);
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => 'une erreur est survenue: ' . $e->getMessage(),
            ], $statusCode);
        }
    }

    public function getUserLogById($id)
    {
        $user = UserLog::findorFail($id);

        if ($user) {
            return response()->json(array_merge(
                $user->toArray(),
                [
                    'user' => $user->user->full_name ?? null,
                ]
            ), 200);
        }

        return response()->json([
            'status' => 404,
            'message' => 'Aucun utilisateur trouvé avec l’ID fourni.'
        ], 404);
    }
    public function getClientLogs(Request $request)
    {
        try {
            $filters = [
                'action' => $request->input('action'),
                'search' => $request->input('search'),
            ];

            $perPage = $request->input('per_page', 10);

            $query = ClientLog::with(['user:id,full_name']);

            $filterableFields = ['action'];


            if (!empty($filters['search'])) {
                $searchQuery = $filters['search'];
                $query->where(function ($q) use ($searchQuery) {
                    $q->whereHas('user', function ($userQuery) use ($searchQuery) {
                        $userQuery->where('first_name', 'LIKE', "%$searchQuery%")
                            ->orWhere('last_name', 'LIKE', "%$searchQuery%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$searchQuery%"]);
                    });
                });
            }

            foreach ($filterableFields as $field) {
                if (!empty($filters[$field])) {
                    $query->where($field, 'REGEXP', $filters[$field]);
                }
            }

            $query->orderBy('created_at', 'desc');
            $clients = $query->paginate($perPage);

            if (!$clients) {
                throw new \Exception('Client introuvable', 404);
            }

            return response()->json([
                'status' => 200,
                'current_page' => $clients->currentPage(),
                'total_client_logs' => $clients->total(),
                'per_page' => $clients->perPage(),
                'client_logs' => $clients->items(),
            ], 200);
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => 'une erreur est survenue: ' . $e->getMessage(),
            ], $statusCode);
        }
    }

    public function getClientLogById($id)
    {
        $user = ClientLog::findorFail($id);

        if ($user) {
            return response()->json(array_merge(
                $user->toArray(),
                [
                    'user' => $user->user->full_name ?? null,
                ]
            ), 200);
        }

        return response()->json([
            'status' => 404,
            'message' => 'Aucun client trouvé avec l’ID fourni.'
        ], 404);
    }
    public function getContactLogs(Request $request)
    {
        try {
            $filters = [
                'action' => $request->input('action'),
                'search' => $request->input('search'),
            ];

            $perPage = $request->input('per_page', 10);

            $query = ContactLog::with(['user:id,full_name']);

            $filterableFields = ['action'];


            if (!empty($filters['search'])) {
                $searchQuery = $filters['search'];
                $query->where(function ($q) use ($searchQuery) {
                    $q->whereHas('user', function ($userQuery) use ($searchQuery) {
                        $userQuery->where('first_name', 'LIKE', "%$searchQuery%")
                            ->orWhere('last_name', 'LIKE', "%$searchQuery%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$searchQuery%"]);
                    });
                });
            }

            foreach ($filterableFields as $field) {
                if (!empty($filters[$field])) {
                    $query->where($field, 'REGEXP', $filters[$field]);
                }
            }

            $query->orderBy('created_at', 'desc');
            $contacts = $query->paginate($perPage);

            if (!$contacts) {
                throw new \Exception('Contact introuvable', 404);
            }

            return response()->json([
                'status' => 200,
                'current_page' => $contacts->currentPage(),
                'total_contact_logs' => $contacts->total(),
                'per_page' => $contacts->perPage(),
                'contact_logs' => $contacts->items(),
            ], 200);
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => 'une erreur est survenue: ' . $e->getMessage(),
            ], $statusCode);
        }
    }

    public function getContactLogById($id)
    {
        $user = ContactLog::findorFail($id);

        if ($user) {
            return response()->json(array_merge(
                $user->toArray(),
                [
                    'user' => $user->user->full_name ?? null,
                ]
            ), 200);
        }

        return response()->json([
            'status' => 404,
            'message' => 'Aucun contact trouvé avec l’ID fourni.'
        ], 404);
    }
    public function getTransactionLogs(Request $request)
    {
        try {
            $filters = [
                'action' => $request->input('action'),
                'search' => $request->input('search'),
            ];

            $perPage = $request->input('per_page', 10);

            $query = CashTransactionLogs::with(['user:id,full_name']);

            $filterableFields = ['action'];


            if (!empty($filters['search'])) {
                $searchQuery = $filters['search'];
                $query->where(function ($q) use ($searchQuery) {
                    $q->whereHas('user', function ($userQuery) use ($searchQuery) {
                        $userQuery->where('first_name', 'LIKE', "%$searchQuery%")
                            ->orWhere('last_name', 'LIKE', "%$searchQuery%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$searchQuery%"]);
                    });
                });
            }

            foreach ($filterableFields as $field) {
                if (!empty($filters[$field])) {
                    $query->where($field, 'REGEXP', $filters[$field]);
                }
            }

            $query->orderBy('created_at', 'desc');
            $transactions = $query->paginate($perPage);

            if (!$transactions) {
                throw new \Exception('Transaction introuvable', 404);
            }

            return response()->json([
                'status' => 200,
                'current_page' => $transactions->currentPage(),
                'total_transaction_logs' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'transaction_logs' => $transactions->items(),
            ], 200);
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => 'une erreur est survenue: ' . $e->getMessage(),
            ], $statusCode);
        }
    }

    public function getTransactionLogById($id)
    {
        $user = CashTransactionLogs::findorFail($id);

        if ($user) {
            return response()->json(array_merge(
                $user->toArray(),
                [
                    'user' => $user->user->full_name ?? null,
                ]
            ), 200);
        }

        return response()->json([
            'status' => 404,
            'message' => 'Aucun transaction trouvé avec l’ID fourni.'
        ], 404);
    }
    public function getPaymentLogs(Request $request)
    {
        try {
            $filters = [
                'action' => $request->input('action'),
                'search' => $request->input('search'),
            ];

            $perPage = $request->input('per_page', 10);

            $query = PaymentLog::with(['user:id,full_name']);

            $filterableFields = ['action'];


            if (!empty($filters['search'])) {
                $searchQuery = $filters['search'];
                $query->where(function ($q) use ($searchQuery) {
                    $q->whereHas('user', function ($userQuery) use ($searchQuery) {
                        $userQuery->where('first_name', 'LIKE', "%$searchQuery%")
                            ->orWhere('last_name', 'LIKE', "%$searchQuery%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$searchQuery%"]);
                    });
                });
            }

            foreach ($filterableFields as $field) {
                if (!empty($filters[$field])) {
                    $query->where($field, 'REGEXP', $filters[$field]);
                }
            }

            $query->orderBy('created_at', 'desc');
            $payments = $query->paginate($perPage);

            if (!$payments) {
                throw new \Exception('Paiement introuvable', 404);
            }

            return response()->json([
                'status' => 200,
                'current_page' => $payments->currentPage(),
                'total_payment_logs' => $payments->total(),
                'per_page' => $payments->perPage(),
                'payment_logs' => $payments->items(),
            ], 200);
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => 'une erreur est survenue: ' . $e->getMessage(),
            ], $statusCode);
        }
    }

    public function getPaymentLogById($id)
    {
        $user = PaymentLog::findorFail($id);

        if ($user) {
            return response()->json(array_merge(
                $user->toArray(),
                [
                    'user' => $user->user->full_name ?? null,
                ]
            ), 200);
        }

        return response()->json([
            'status' => 404,
            'message' => 'Aucun payment trouvé avec l’ID fourni.'
        ], 404);
    }
    public function getRecoveryLogs(Request $request)
    {
        try {
            $filters = [
                'action' => $request->input('action'),
                'search' => $request->input('search'),
            ];

            $perPage = $request->input('per_page', 10);

            $query = RecoveryLog::with(['user:id,full_name']);

            $filterableFields = ['action'];


            if (!empty($filters['search'])) {
                $searchQuery = $filters['search'];
                $query->where(function ($q) use ($searchQuery) {
                    $q->whereHas('user', function ($userQuery) use ($searchQuery) {
                        $userQuery->where('first_name', 'LIKE', "%$searchQuery%")
                            ->orWhere('last_name', 'LIKE', "%$searchQuery%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$searchQuery%"]);
                    });
                });
            }

            foreach ($filterableFields as $field) {
                if (!empty($filters[$field])) {
                    $query->where($field, 'REGEXP', $filters[$field]);
                }
            }

            $query->orderBy('created_at', 'desc');
            $recoveries = $query->paginate($perPage);

            if (!$recoveries) {
                throw new \Exception('Recouvrement introuvable', 404);
            }

            return response()->json([
                'status' => 200,
                'current_page' => $recoveries->currentPage(),
                'total_recovery_logs' => $recoveries->total(),
                'per_page' => $recoveries->perPage(),
                'recovery_logs' => $recoveries->items(),
            ], 200);
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => 'une erreur est survenue: ' . $e->getMessage(),
            ], $statusCode);
        }
    }

    public function getRecoveryLogById($id)
    {
        $user = RecoveryLog::findorFail($id);

        if ($user) {
            return response()->json(array_merge(
                $user->toArray(),
                [
                    'user' => $user->user->full_name ?? null,
                ]
            ), 200);
        }

        return response()->json([
            'status' => 404,
            'message' => 'Aucun recouvrement trouvé avec l\'ID fourni.'
        ], 404);
    }

    public function getCashLogs(Request $request)
    {
        try {
            $filters = [
                'action' => $request->input('action'),
                'search' => $request->input('search'),
            ];

            $perPage = $request->input('per_page', 10);

            $query = CashTransactionLogs::with(['user:id,full_name']);

            $filterableFields = ['action'];
            if (!empty($filters['search'])) {
                $searchQuery = $filters['search'];
                $query->where(function ($q) use ($searchQuery) {
                    $q->whereHas('user', function ($userQuery) use ($searchQuery) {
                        $userQuery->where('first_name', 'LIKE', "%$searchQuery%")
                            ->orWhere('last_name', 'LIKE', "%$searchQuery%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$searchQuery%"]);
                    });
                });
            }
            foreach ($filterableFields as $field) {
                if (!empty($filters[$field])) {
                    $query->where($field, 'REGEXP', $filters[$field]);
                }
            }
            $query->orderBy('created_at', 'desc');
            $cashLogs = $query->paginate($perPage);

            if (!$cashLogs) {
                throw new \Exception('Cash log introuvable', 404);
            }

            return response()->json([
                'status' => 200,
                'current_page' => $cashLogs->currentPage(),
                'total_cash_logs' => $cashLogs->total(),
                'per_page' => $cashLogs->perPage(),
                'cash_logs' => $cashLogs->items(),
            ], 200);
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => 'une erreur est survenue: ' . $e->getMessage(),
            ], $statusCode);
        }
    }
    public function getCashLogById($id)
    {
        $user = CashTransactionLogs::findorFail($id);

        if ($user) {
            return response()->json(array_merge(
                $user->toArray(),
                [
                    'user' => $user->user->full_name ?? null,
                ]
            ), 200);
        }

        return response()->json([
            'status' => 404,
            'message' => 'Aucun cash log trouvé avec l’ID fourni.'
        ], 404);
    }

    public function getDocumentLogs(Request $request)
    {
        try {
            $filters = [
                'action' => $request->input('action'),
                'document_type' => $request->input('document_type'),
                'search' => $request->input('search'),
            ];

            $perPage = $request->input('per_page', 10);

            $query = DocumentLog::query();

            $query->with(['user:id,full_name']);

            $filterableFields = ['action', 'document_type'];

            if (!empty($filters['search'])) {
                $searchQuery = $filters['search'];
                $query->where(function ($q) use ($searchQuery) {
                    $q->whereHas('user', function ($userQuery) use ($searchQuery) {
                        $userQuery->where('first_name', 'LIKE', "%$searchQuery%")
                            ->orWhere('last_name', 'LIKE', "%$searchQuery%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$searchQuery%"]);
                    });
                });
            }

            foreach ($filterableFields as $field) {
                if (!empty($filters[$field])) {
                    $query->where($field, 'REGEXP', $filters[$field]);
                }
            }

            $query->orderBy('created_at', 'desc');

            $documentLogs = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'current_page' => $documentLogs->currentPage(),
                'total_document_logs' => $documentLogs->total(),
                'per_page' => $documentLogs->perPage(),
                'document_logs' => $documentLogs->items(),
            ], 200);
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => 'une erreur est survenue: ' . $e->getMessage(),
            ], $statusCode);
        }
    }
    public function getDocumentLogById(Request $request, $id)
    {
        try {
            $query = DocumentLog::with(['user:id,full_name'])
                ->where('id', $id);

            $documentType = $request->input('document_type');
            if (!empty($documentType)) {
                $query->where('document_type', 'REGEXP', $documentType);
            }

            $documentLog = $query->first();

            if (!$documentLog) {
                throw new \Exception('Document log introuvable', 404);
            }

            return response()->json(array_merge(
                $documentLog->toArray(),
                [
                    'user' => $documentLog->user->full_name ?? null,
                ]
            ), 200);

        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => 'une erreur est survenue: ' . $e->getMessage(),
            ], $statusCode);
        }
    }
    public function getDocumentItemLogs(Request $request)
    {
        try {
            $filters = [
                'action' => $request->input('action'),
                'item_type' => $request->input('item_type'),
                'search' => $request->input('search'),
            ];

            $perPage = $request->input('per_page', 10);

            $query = ItemLog::query();

            $query->with(['user:id,full_name']);

            $filterableFields = ['action', 'item_type'];

            if (!empty($filters['search'])) {
                $searchQuery = $filters['search'];
                $query->where(function ($q) use ($searchQuery) {
                    $q->whereHas('user', function ($userQuery) use ($searchQuery) {
                        $userQuery->where('first_name', 'LIKE', "%$searchQuery%")
                            ->orWhere('last_name', 'LIKE', "%$searchQuery%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$searchQuery%"]);
                    });
                });
            }

            foreach ($filterableFields as $field) {
                if (!empty($filters[$field])) {
                    $query->where($field, 'REGEXP', $filters[$field]);
                }
            }

            $query->orderBy('created_at', 'desc');

            $documentLogs = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'current_page' => $documentLogs->currentPage(),
                'total_item_logs' => $documentLogs->total(),
                'per_page' => $documentLogs->perPage(),
                'item_logs' => $documentLogs->items(),
            ], 200);
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => 'une erreur est survenue: ' . $e->getMessage(),
            ], $statusCode);
        }
    }
    public function getDocumentItemLogById(Request $request, $id)
    {
        try {
            $query = ItemLog::with(['user:id,full_name'])
                ->where('id', $id);

            $documentType = $request->input('item_type');
            if (!empty($documentType)) {
                $query->where('item_type', 'REGEXP', $documentType);
            }

            $documentLog = $query->first();

            if (!$documentLog) {
                throw new \Exception('Document log introuvable', 404);
            }

            return response()->json(array_merge(
                $documentLog->toArray(),
                [
                    'user' => $documentLog->user->full_name ?? null,
                ]
            ), 200);

        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => 'une erreur est survenue: ' . $e->getMessage(),
            ], $statusCode);
        }
    }

}
