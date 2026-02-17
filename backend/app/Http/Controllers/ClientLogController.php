<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\ClientLogRepositoryInterface;
use App\Repositories\ClientRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


class ClientLogController extends Controller
{
    protected $clientRepository;
    protected $clientLogRepository;

    public function __construct(ClientRepository $clientRepository, ClientLogRepositoryInterface $clientLogRepository)
    {
        $this->clientRepository = $clientRepository;
        $this->clientLogRepository = $clientLogRepository;
    }

    public function updateClient(Request $request, $id)
    {
        $updatedClient = $this->clientRepository->update($id, $request->all());
        return response()->json(['message' => 'Client mis à jour avec succès', 'client' => $updatedClient]);
    }

    public function deleteClient($id)
    {
        $this->clientRepository->delete($id);
        return response()->json(['message' => 'Client supprimé avec succès']);
    }
}
