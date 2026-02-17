<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\UserLogRepositoryInterface;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


class UserLogController extends Controller
{
    protected $UserRepository;
    protected $UserLogRepository;

    public function __construct(UserRepository $UserRepository, UserLogRepositoryInterface $UserLogRepository)
    {
        $this->UserRepository = $UserRepository;
        $this->UserLogRepository = $UserLogRepository;
    }

    public function updateUser(Request $request, $id)
    {
        $updatedClient = $this->UserRepository->update($id, $request->all());
        return response()->json(['message' => 'User mis à jour avec succès', 'client' => $updatedClient]);
    }

    public function deleteUser($id)
    {
        $this->UserRepository->delete($id);
        return response()->json(['message' => 'User supprimé avec succès']);
    }
    
}
