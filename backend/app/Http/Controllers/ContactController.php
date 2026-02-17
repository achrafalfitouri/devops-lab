<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\ContactRepositoryInterface;
use App\Repositories\Contracts\ContactLogRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    protected $contactRepository;
    protected $contactLogRepository;

    public function __construct(
        ContactRepositoryInterface $contactRepository,
        ContactLogRepositoryInterface $contactLogRepository
    ) {
        $this->contactRepository = $contactRepository;
        $this->contactLogRepository = $contactLogRepository;
    }

    public function getContact(Request $request)
    {
        try {
            $clientId = $request->input('client_id');

            $query = $this->contactRepository->getAllContacts()->with('client');

            if ($clientId) {
                $query->where('client_id', $clientId);
            }

            $contacts = $query->orderBy('created_at', 'desc')->get();



            return response()->json(['contacts' => $contacts]);
        } catch (Exception $e) {
            Log::error("Échec de la récupération des contacts: {$e->getMessage()}");
            return response()->json(['message' => 'Échec de la récupération des contacts', 'error' => $e->getMessage()], 500);
        }
    }



    public function getById($id)
    {
        try {
            $contact = $this->contactRepository->getById($id);
            if (!$contact) {
                return response()->json(['message' => 'Contact introuvable'], 404);
            }
            return response()->json($contact);
        } catch (Exception $e) {
            Log::error("Échec de la récupération du contact avec l'ID {$id}: {$e->getMessage()}");
            return response()->json(['message' => 'Échec de la récupération du contact', 'error' => $e->getMessage()], 500);
        }
    }

    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:20',
                'phone' => 'required|max:255',
                'email' => 'required|email|max:255',
                'client_id' => 'nullable|exists:clients,id'
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first(), 422);
            }

            $data = $request->all();
            $data['full_name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

            $data = array_map(function ($value) {
                return empty($value) && $value !== 0 ? null : $value;
            }, $data);

            $contact = $this->contactRepository->create($data);

            return response()->json($contact, 201);
        } catch (Exception $e) {
            Log::error("Échec de la création de contact: {$e->getMessage()}");
            return response()->json([
                'message' => 'Échec de la création du contact',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();

            if (isset($data['first_name']) && isset($data['last_name'])) {
                $data['full_name'] = $data['first_name'] . ' ' . $data['last_name'];
            }

            $contact = $this->contactRepository->getById($id);

            if ($contact) {
                $data['full_name'] = trim(($request->filled('first_name') ? $request->first_name : $contact->first_name) . ' ' .
                    ($request->filled('last_name') ? $request->last_name : $contact->last_name));
            } else {
                return response()->json(['message' => 'Contact introuvable'], 404);
            }
            $data = array_map(function ($value) {
                return empty($value) && $value !== 0 ? null : $value;
            }, $data);
            $contact = $this->contactRepository->update($id, $data);

            return response()->json($contact);
        } catch (Exception $e) {
            return response()->json(['message' => 'Échec de la mise à jour du contact', 'error' => $e->getMessage()], 500);
        }
    }



    public function delete($id)
    {
        try {
            $deleted = $this->contactRepository->delete($id);
            if (!$deleted) {
                return response()->json(['message' => 'Contact introuvable'], 404);
            }
            return response()->json(['message' => 'Contact supprimé avec succès']);
        } catch (Exception $e) {
            Log::error("Échec de la suppression du contact avec l'ID{$id}: {$e->getMessage()}");
            return response()->json(['message' => 'Échec de la suppression du contact', 'error' => $e->getMessage()], 500);
        }
    }
}
