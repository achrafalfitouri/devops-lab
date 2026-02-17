<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\ContactLogRepositoryInterface;
use App\Repositories\ContactRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


class ContactLogController extends Controller
{
    protected $ContactRepository;
    protected $ContactLogRepository;

    public function __construct(ContactRepository $ContactRepository, ContactLogRepositoryInterface $ContactLogRepository)
    {
        $this->ContactRepository = $ContactRepository;
        $this->ContactLogRepository = $ContactLogRepository;
    }

    public function updateClient(Request $request, $id)
    {
        $updatedClient = $this->ContactRepository->update($id, $request->all());
        return response()->json(['error' => 'Client mis à jour avec succès', 'client' => $updatedClient]);
    }

    public function deleteClient($id)
    {
        $this->ContactRepository->delete($id);
        return response()->json(['error' => 'Client supprimé avec succès']);
    }
}
