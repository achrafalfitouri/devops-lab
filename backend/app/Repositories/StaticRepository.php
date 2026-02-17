<?php

namespace App\Repositories;

use App\Models\BusinessSector;
use App\Models\CashRegister;
use App\Models\CashTransactionType;
use App\Models\City;
use App\Models\Client;
use App\Models\ClientType;
use App\Models\DeliveryNote;
use App\Models\EmailTemplate;
use App\Models\Gamutes;
use App\Models\Invoice;
use App\Models\InvoiceCredit;
use App\Models\OrderNote;
use App\Models\OrderReceipt;
use App\Models\PaymentType;
use App\Models\Quote;
use App\Models\RefundNote;
use App\Models\Role;
use App\Models\Status;
use App\Models\Titles;
use App\Models\User;
use App\Models\UserCashRegister;
use Illuminate\Support\Facades\DB;
use App\Repositories\Contracts\StaticRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class StaticRepository implements StaticRepositoryInterface
{
    public function getAllData()
    {
        $cities = City::select('id', 'name')
            ->orderByRaw("CASE
                WHEN name = 'Marrakech' THEN 1
                WHEN name = 'Casablanca' THEN 2
                WHEN name = 'Rabat' THEN 3
                WHEN name = 'Agadir' THEN 4
                ELSE 5
            END")
            ->orderBy('name', 'asc')
            ->get();

        $clientTypes = ClientType::select('id', 'name')->get();
        $gamuts = Gamutes::select('id', 'name')->get();
        $statuses = Status::select('id', 'name')->get();
        $business_sectors = BusinessSector::select('id', 'name')->get();

        $cities_filter = City::select('id', 'name')
            ->orderByRaw("CASE
                WHEN name = 'Marrakech' THEN 1
                WHEN name = 'Casablanca' THEN 2
                WHEN name = 'Rabat' THEN 3
                WHEN name = 'Agadir' THEN 4
                ELSE 5
            END")
            ->orderBy('name', 'asc')
            ->get();

        $clientTypes_filter = ClientType::select('id', 'name')->get();
        $gamuts_filter = Gamutes::select('id', 'name')->get();
        $statuses_filter = Status::select('id', 'name')->get();
        $business_sectors_filter = BusinessSector::select('id', 'name')->get();

        return [
            'cities' => $cities,
            'client_types' => $clientTypes,
            'gamutes' => $gamuts,
            'statuses' => $statuses,
            'business_sectors' => $business_sectors,
            'cities_filter' => $cities_filter,
            'client_types_filter' => $clientTypes_filter,
            'gamutes_filter' => $gamuts_filter,
            'statuses_filter' => $statuses_filter,
            'business_sectors_filter' => $business_sectors_filter,
        ];
    }
    /**
     *
     * @return array
     */
    public function getAllDataUser()
    {
        $title = Titles::select('id', 'name')->get();
        $titleFilter = Titles::select('id', 'name')->get();
        $role = Role::select('id', 'name', 'title', 'description', 'icon')
            // Custom ordering: Gérer before Consulter; users first, then permissions, logs, then others; non Gérer/Consulter last
            ->orderByRaw("
                CASE
                    WHEN name LIKE 'user_%' THEN 1
                    WHEN name LIKE 'permission_%' THEN 2
                    WHEN name LIKE 'logs_%' THEN 3
                    WHEN name LIKE 'client_%' THEN 4
                    WHEN name LIKE 'transaction_%' THEN 5
                    WHEN name LIKE 'cashregister_%' THEN 6
                    WHEN name LIKE 'document_%' THEN 7
                    WHEN name LIKE 'payment_%' THEN 8
                    WHEN name LIKE 'email_%' THEN 9
                    ELSE 100
                END
            ")
            ->orderByRaw("
                CASE
                    WHEN name LIKE '%_manager' THEN 0
                    WHEN name LIKE '%_viewer' THEN 1
                    ELSE 2
                END
            ")
            ->orderBy('title')
            ->get();
        $roleFilter = Role::select('id', 'title', 'description')
            ->orderByRaw("
                CASE
                    WHEN name LIKE 'user_%' THEN 1
                    WHEN name LIKE 'permission_%' THEN 2
                    WHEN name LIKE 'logs_%' THEN 3
                    WHEN name LIKE 'client_%' THEN 4
                    WHEN name LIKE 'transaction_%' THEN 5
                    WHEN name LIKE 'cashregister_%' THEN 6
                    WHEN name LIKE 'document_%' THEN 7
                    WHEN name LIKE 'payment_%' THEN 8
                    WHEN name LIKE 'email_%' THEN 9
                    ELSE 100
                END
            ")
            ->orderByRaw("
                CASE
                    WHEN name LIKE '%_manager' THEN 0
                    WHEN name LIKE '%_viewer' THEN 1
                    ELSE 2
                END
            ")
            ->orderBy('title')
            ->get();
        $cr = CashRegister::select('id', 'name')->get();
        $statusFilter = collect([
            (object) ['id' => 'true', 'name' => 'Active'],
            (object) ['id' => 'false', 'name' => 'Inactive']
        ]);
        $user = User::select('id', 'full_name')->get();

        return [
            'title' => $title,
            'title_filter' => $titleFilter,
            'role' => $role,
            'cashregister' => $cr,
            'role_filter' => $roleFilter,
            'status_filter' => $statusFilter,
            'user' =>  $user
        ];
    }
    public function getAllDataTransaction()
    {
        $authUser = Auth::user();
        $userId = $authUser->id;


        $usercash = UserCashRegister::select('id', 'user_id', 'cash_register_id')
            ->where('user_id', $userId)
            ->get();

        $cashRegisterFilter = CashRegister::select('id', 'name', 'code', 'balance')
            ->whereIn('id', $usercash->pluck('cash_register_id'))
            ->get();

        $cashRegister = CashRegister::select('id', 'name', 'code', 'balance')->get();

        $transactionType = CashTransactionType::select('id', 'name', 'sign')
            ->orderBy('name', 'asc')
            ->get();
        $transactionTypeFilter = CashTransactionType::select('id', 'name', 'sign')
            ->orderBy('name', 'asc')
            ->get();
        $users = User::select('id', 'full_name')->get();
        $clients = Client::select('clients.id', 'clients.legal_name', 'clients.ice', 'statuses.name as status')
            ->join('statuses', 'clients.status_id', '=', 'statuses.id')
            ->get();
        $refund = RefundNote::select('id', 'code')
            ->where('status', 'Brouillon')
            ->get();
        return [
            'transaction_type' => $transactionType,
            'transaction_type_filter' => $transactionTypeFilter,
            'cash_register_filter' => $cashRegisterFilter,
            'cash_register' => $cashRegister,
            'user' => $users,
            'client' => $clients,
            'refund' => $refund,
            'user_cash' => $usercash

        ];
    }


    public function getDocuments()
    {
        $clients = Client::select('clients.id', 'clients.legal_name', 'clients.ice', 'statuses.name as status')
            ->join('statuses', 'clients.status_id', '=', 'statuses.id')
            ->where('clients.legal_name', '!=', 'Client de passage')
            ->get();
        $client_filter = Client::select('clients.id', 'clients.legal_name', 'clients.ice', 'statuses.name as status')
            ->join('statuses', 'clients.status_id', '=', 'statuses.id')
            ->where('clients.legal_name', '!=', 'Client de passage')
            ->get();

        $users = User::select('id', 'full_name')->get();
        $user_filter = User::select('id', 'full_name')->get();

        return [
            'clients' => $clients,
            'users' => $users,
            'client_filter' => $client_filter,
            'user_filter' => $user_filter,
        ];
    }

    public function getContact()
    {
        $client = Client::select('id', 'legal_name')->get();

        return [
            'clients' => $client,
        ];
    }

    public function getPaymentType()
    {
        $payment = PaymentType::all()->map(function ($item) {

            $paymentTypeMapping = [
                'ESPECE' => 'Espèce',
                'VIREMENT' => 'Virement',
                'CHEQUE' => 'Chèque',
                'EFFET' => 'Effet'
            ];

            $item->name = $paymentTypeMapping[strtolower($item->name)] ?? ucfirst($item->name);
            return $item;
        });
        
        $recovery = PaymentType::whereNotIn('name', ['Espèce'])
            ->get()
            ->map(function ($item) {

            $recoveryTypeMapping = [
            'VIREMENT' => 'Virement',
            'CHEQUE' => 'Chèque',
            'EFFET' => 'Effet'
            ];

            $item->name = $recoveryTypeMapping[strtolower($item->name)] ?? ucfirst($item->name);
            return $item;
        });

        $client = Client::select('clients.id', 'clients.legal_name', 'clients.ice', 'statuses.name as status')
            ->join('statuses', 'clients.status_id', '=', 'statuses.id')
            ->where('clients.legal_name', '!=', 'Client de passage')
            ->get();


        return [
            'payment_type' => $payment,
            'client' => $client,
            'recovery_type' => $recovery,
        ];
    }

    public function getClienFortEmailSelect()
    {
        $client = Client::select('clients.id', 'clients.legal_name', 'clients.ice', 'statuses.name as status')
            ->join('statuses', 'clients.status_id', '=', 'statuses.id')
            ->where('clients.legal_name', '!=', 'Client de passage')
            ->get();

        return [
            'clients' => $client,
        ];
    }


    public function getClientContactForEmailSelect($id)
    {
        $id = (string) $id;

        $contacts = DB::select("
            SELECT id, full_name, client_id
            FROM contacts
            WHERE client_id = ? AND deleted_at IS NULL
        ", [$id]);

        return ['contacts' => $contacts];
    }

    public function getClientDocumentCodesForEmailSelect($id, $type)
    {
        $models = [
            'Devis' => Quote::class,
            'Bon de commande' => OrderNote::class,
            'Bon de livraison' => DeliveryNote::class,
            'Facture' => Invoice::class,
            'Reçu de commande' => OrderReceipt::class,
            'Facture avoir' => InvoiceCredit::class,

        ];

        if (array_key_exists($type, $models)) {
            $model = $models[$type];
            $documents = $model::select('id', 'code')->where('client_id', $id)->get();
        } else {
            return response()->json(['error' => 'Invalid type'], 400);
        }
        return [
            'document_codes' => $documents,
        ];
    }



    public function getDataTest()
    {
        $payment = PaymentType::select('id', 'name')->get();

        return [
            'payment_type' => $payment,
        ];
    }

    public function getTemplate()
    {
        $temp = EmailTemplate::select('id', 'name')->get();

        return [
            'email_template' => $temp,
        ];
    }

    public function getRecoveriesForPayment($paymentTypeId = null, $clientId = null, $recoveryId = null)
    {
        // If recovery_id is provided, fetch that specific recovery
        if ($recoveryId) {
            $recoveries = DB::table('recoveries')
                ->join('payment_types', 'recoveries.payment_type_id', '=', 'payment_types.id')
                ->where('recoveries.id', $recoveryId)
                ->where('recoveries.recovery_balance', '>', 0)
                ->whereNull('recoveries.deleted_at')
                ->select(
                    'recoveries.id', 
                    'recoveries.code', 
                    'recoveries.amount', 
                    'recoveries.recovery_balance', 
                    'payment_types.name as payment_type',
                    'recoveries.check_number',
                    'recoveries.wire_transfer_number',
                    'recoveries.effect_number',
                    DB::raw("CONCAT(recoveries.code, ' - ', recoveries.amount) as display")
                )
                ->get();
        } else {
            // Otherwise, filter by payment type and client
            $recoveries = DB::table('recoveries')
                ->join('payment_types', 'recoveries.payment_type_id', '=', 'payment_types.id')
                ->where('recoveries.payment_type_id', $paymentTypeId)
                ->where('recoveries.client_id', $clientId)
                ->where('recoveries.recovery_balance', '>', 0)
                ->whereNull('recoveries.deleted_at')
                ->select(
                    'recoveries.id', 
                    'recoveries.code', 
                    'recoveries.amount', 
                    'recoveries.recovery_balance', 
                    'payment_types.name as payment_type',
                    'recoveries.check_number',
                    'recoveries.wire_transfer_number',
                    'recoveries.effect_number',
                    DB::raw("CONCAT(recoveries.code, ' - ', recoveries.amount) as display")
                )
                ->get();
        }

        return [
            'recoveries' => $recoveries,
        ];
    }
}
