<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\StaticRepositoryInterface;
use Illuminate\Routing\Controller;

class StaticController extends Controller
{
    protected $staticRepository;

    public function __construct(StaticRepositoryInterface $staticRepository)
    {
        $this->staticRepository = $staticRepository;
    }

    public function getAllData()
    {
        try {
            $data = $this->staticRepository->getAllData();

            if (is_array($data) && empty($data)) {
                return response()->json([
                    'status' => 204,
                    'error' => 'Aucune donnée trouvée'
                ], 204);
            }

            return response()->json([
                'status' => 200,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "Une erreur s'est produite lors de la récupération des données",
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getAllDataUser()
    {
        try {
            $data = $this->staticRepository->getAllDataUser();

            if (is_array($data) && empty($data)) {
                return response()->json([
                    'status' => 204,
                    'error' => 'Aucune donnée trouvée'
                ], 204);
            }

            return response()->json([
                'status' => 200,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "Une erreur s'est produite lors de la récupération des données",
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getAllDataTransaction()
    {
        try {
            $data = $this->staticRepository->getAllDataTransaction();

            if (is_array($data) && empty($data)) {
                return response()->json([
                    'status' => 204,
                    'error' => 'Aucune donnée trouvée'
                ], 204);
            }

            return response()->json([
                'status' => 200,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "Une erreur s'est produite lors de la récupération des données",
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getDocuments()
    {
        try {
            $data = $this->staticRepository->getDocuments();

            if (is_array($data) && empty($data)) {
                return response()->json([
                    'status' => 204,
                    'error' => 'Aucune donnée trouvée'
                ], 204);
            }

            return response()->json([
                'status' => 200,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "Une erreur s'est produite lors de la récupération des données",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getContact()
    {
        try {
            $data = $this->staticRepository->getContact();

            if (is_array($data) && empty($data)) {
                return response()->json([
                    'status' => 204,
                    'error' => 'Aucune donnée trouvée'
                ], 204);
            }

            return response()->json([
                'status' => 200,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "Une erreur s'est produite lors de la récupération des données",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPaymentType()
    {
        try {
            $data = $this->staticRepository->getPaymentType();

            if (is_array($data) && empty($data)) {
                return response()->json([
                    'status' => 204,
                    'error' => 'Aucune donnée trouvée'
                ], 204);
            }

            return response()->json([
                'status' => 200,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "Une erreur s'est produite lors de la récupération des données",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getClienFortEmailSelect()
    {
        try {
            $data = $this->staticRepository->getClienFortEmailSelect();

            if (is_array($data) && empty($data)) {
                return response()->json([
                    'status' => 204,
                    'error' => 'No data found'
                ], 204);
            }

            return response()->json([
                'status' => 200,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while retrieving data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getClientContactForEmailSelect($id)
    {
        try {
            $data = $this->staticRepository->getClientContactForEmailSelect($id);

            if (is_array($data) && empty($data)) {
                return response()->json([
                    'status' => 204,
                    'error' => 'No data found'
                ], 204);
            }

            return response()->json([
                'status' => 200,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while retrieving data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getClientDocumentCodesForEmailSelect($id, $type)
    {
        try {
            $data = $this->staticRepository->getClientDocumentCodesForEmailSelect($id, $type);

            if (is_array($data) && empty($data)) {
                return response()->json([
                    'status' => 204,
                    'error' => 'No data found'
                ], 204);
            }

            return response()->json([
                'status' => 200,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while retrieving data',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getTestt()
    {
        try {
            $data = $this->staticRepository->getDataTest();

            if (is_array($data) && empty($data)) {
                return response()->json([
                    'status' => 204,
                    'error' => 'Aucune donnée trouvée'
                ], 204);
            }

            return response()->json([
                'status' => 200,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "Une erreur s'est produite lors de la récupération des données",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTemplate()
    {
        try {
            $data = $this->staticRepository->getTemplate();

            if (is_array($data) && empty($data)) {
                return response()->json([
                    'status' => 204,
                    'error' => 'No data found'
                ], 204);
            }

            return response()->json([
                'status' => 200,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while retrieving data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getRecoveriesForPayment()
    {
        try {
            $recoveryId = request('recovery_id');
            $paymentTypeId = request('payment_type_id');
            $clientId = request('client_id');

            // If recovery_id is provided, use it directly
            if ($recoveryId) {
                $data = $this->staticRepository->getRecoveriesForPayment($paymentTypeId, $clientId, $recoveryId);
            } else {
                // Otherwise, payment_type_id and client_id are required
                if (!$paymentTypeId || !$clientId) {
                    return response()->json([
                        'status' => 400,
                        'error' => 'payment_type_id et client_id sont requis (ou recovery_id)'
                    ], 400);
                }
                $data = $this->staticRepository->getRecoveriesForPayment($paymentTypeId, $clientId);
            }

            if (empty($data)) {
                return response()->json([
                    'status' => 200,
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 200,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => 'Une erreur s\'est produite lors de la récupération des recouvrements',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
