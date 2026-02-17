<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\CashLogRepositoryInterface;
use App\Repositories\TransactionRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


class CashLogController extends Controller
{
    protected $TransactionRepository;
    protected $CashLogrepository;

    public function __construct(TransactionRepository $TransactionRepository, CashLogRepositoryInterface $CashLogrepository)
    {
        $this->TransactionRepository = $TransactionRepository;
        $this->CashLogrepository = $CashLogrepository;
    }

    public function updateClient(Request $request, $id)
    {
        $updatedClient = $this->TransactionRepository->updateTransaction($id, $request->all());
        return response()->json(['error' => 'Mis à jour avec succès', 'transaction' => $updatedClient]);
    }

    public function deleteClient($id)
    {
        $this->TransactionRepository->softDeleteTransaction($id);
        return response()->json(['error' => ' Supprimé avec succès']);
    }
}
