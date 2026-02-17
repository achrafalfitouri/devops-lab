<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use App\Exports\ClientExport;
use App\Helpers\FilterHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    protected $repository;

    public function __construct(ClientRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }


    public function getClients(Request $request)
    {
        $filters = [
            'gamut_id' => $request->input('gamut'),
            'status_id' => $request->input('status'),
            'client_type_id' => $request->input('type'),
            'city_id' => $request->input('city'),
            'business_sector_id' => $request->input('business_sector'),
            'search' => $request->input('search'),
            'archive' => $request->input('archive', false),
        ];

        $perPage = $request->input('per_page', 10);
        if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
            $query = $this->repository->getClients()->onlyTrashed();
        } else {
            $query = $this->repository->getClients()->with([
                'city:id,name',
                'clientType:id,name',
                'gamut:id,name',
                'status:id,name',
                'businessSector:id,name'
            ]);        }


        $filterableFields = ['gamut_id', 'status_id', 'client_type_id', 'city_id', 'business_sector_id'];
        $searchableFields = ['code', 'legal_name', 'trade_name'];

        $query = FilterHelper::applyFilters($query, $filters, $filterableFields);
        $query = FilterHelper::applySearch($query, $filters['search'] ?? null, $searchableFields);

        $query->orderBy('created_at', 'desc');

        $clients = $query->paginate($perPage);

        return response()->json([
            'status' => 200,
            'current_page' => $clients->currentPage(),
            'total_clients' => $clients->total(),
            'per_page' => $clients->perPage(),
            'clients' =>$clients->items()
        ]);
    }

    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'legal_name' => [
                    'required', 'string', 'max:255',
                    Rule::unique('clients')->whereNull('deleted_at')
                ],
                'trade_name' => 'required|string|max:255',
            
                'status' => 'required|string|max:50'
            ], [
                'legal_name.unique' => 'Ce nom légal existe déjà',
                'ice.unique' => 'Ce numéro ICE existe déjà'
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first(), 422);
            }

            $data = $request->only([
                'legal_name',
                'trade_name',
                'phone_number',
                'email',
                'city_id',
                'address',
                'ice',
                'if',
                'tp',
                'rc',
                'client_type_id',
                'type_id',
                'gamut_id',
                'status_id',
                'business_sector_id',
            ]);
            $data['balance'] = 0;
            $data = array_map(function ($value) {
                return empty($value) && $value !== 0 ? null : $value;
            }, $data);
            $client = $this->repository->create($data);

            return response()->json([
                'status' => 200,
                'message' => 'Client créé avec succès',
                'id' => $client->id
            ]);
        } catch (\Exception $e) {
            throw new \Exception(
                'Échec du traitement de la demande : ' . $e->getMessage(),
                $e->getCode() ?: 500
            );
        }
    }


    public function getClientById(Request $request,$id)
    {
        $isArchived = filter_var($request->query('archive'), FILTER_VALIDATE_BOOLEAN);

        if ($isArchived) {
            $client = $this->repository->getClients()->onlyTrashed()->where('id', $id)->first();
        } else {
            $client = $this->repository->findById($id);
        }

        if (!$client) {
            throw new ModelNotFoundException('Aucun client trouvé avec l ID fourni');
        }

        return response()->json(array_merge(
            $client->toArray(),
            [
                'city' => $client->city->name ?? null,
                'type' => $client->clientType->name ?? null,
                'gamut' => $client->gamut->name ?? null,
                'status' => $client->status->name ?? null,
                'business_sector' => $client->businessSector->name ?? null,
            ]
        ), 200);

    }
    public function uploadLogo(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'nullable|image|mimes:png,jpeg,jpg|max:5000',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $client = $this->repository->findById($id);

        if (!$client) {
            throw new ModelNotFoundException('Client introuvable');
        }

        if ($request->hasFile('logo')) {

            $file = $request->file('logo');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $relativePath = 'uploads/' . $filename;
            $file->move(public_path('uploads'), $filename);

            $updated = $this->repository->update($id, ['logo' => $relativePath]);
            if (!$updated) {
                throw new \Exception('Échec de la mise à jour du logo', 500);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Logo téléchargé avec succès',
                'logo' => $relativePath
            ]);
        }

        throw new \Exception('Aucun fichier logo trouvé dans la demande', 400);
    }


    public function update(Request $request, $id)
    {
        $data = $request->only([
            'legal_name',
            'trade_name',
            'phone_number',
            'email',
            'city_id',
            'address',
            'ice',
            'if',
            'tp',
            'rc',
            'client_type_id',
            'gamut_id',
            'status_id',
            'business_sector_id',
            'balance'
        ]);

        $client = $this->repository->findById($id);

        if (!$client) {
            throw new ModelNotFoundException('Client introuvable');
        }
        if ($client->legal_name === 'Client de passage') {
            return response()->json(['message' => 'Ce client ne peut pas être modifié.'], 403);
        }
        if (isset($data['code']) && $data['code'] === $client->code) {
            unset($data['code']);
        } else if (isset($data['code'])) {
            $validator = Validator::make($data, [
                'code' => 'unique:clients,code,' . $id,
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Ce code est déjà utilisé par un autre document.'], 422);
            }
        }
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }
        $data = array_map(function ($value) {
            return empty($value) && $value !== 0 ? null : $value;
        }, $data);
        $updated = $this->repository->update($id, $data);

        if (!$updated) {
            throw new \Exception('Échec de la mise à jour du client', 500);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Client mis à jour avec succès'
        ]);
    }
    public function softDelete($id)
    {
        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            throw new ModelNotFoundException('Client introuvable');
        }

        return response()->json([
            'status' => 200,
            'message' => 'Client supprimé avec succès',
            'deleted_by_user_id' => Auth::check() ? Auth::id() : null
        ]);
    }
    public function exportClients(Request $request)
    {
        $filters = [
            'gamut_id' => $request->input('gamut'),
            'status_id' => $request->input('status'),
            'client_type_id' => $request->input('type'),
            'city_id' => $request->input('city'),
            'business_sector_id' => $request->input('business_sector'),
            'search' => $request->input('search'),
        ];

        $perPage = $request->input('per_page', 10);

        $fileName = 'clients_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        $filePath = storage_path('app/public/' . $fileName);

        $export = new ClientExport($filters, $perPage);

        Excel::store($export, 'public/' . $fileName);

        $downloadUrl = asset('storage/' . $fileName);

        return response()->json([
            'status' => 200,
            'message' => 'Clients exportés avec succès.',
            'download_url' => $downloadUrl,
        ], 200);
    }
    public function getClientsByBusinessSector()
    {
        $clientsBySector = $this->repository->getClients()
            ->with('businessSector')
            ->get()
            ->filter(fn($client) => $client->businessSector !== null)
            ->groupBy('business_sector_id')
            ->map(function ($clients) {
                return [
                    'sector_name' => $clients->first()->businessSector->name,
                    'client_count' => $clients->count()
                ];
            });

        $sortedSectors = $clientsBySector->sortByDesc('client_count');

        $topSectors = $sortedSectors->take(4);

        $others = $sortedSectors->slice(4)->sum('client_count');

        $finalResult = $topSectors->values()->toArray();

        if ($others > 0) {
            $finalResult[] = [
                'sector_name' => 'Autres',
                'client_count' => $others
            ];
        }

        return response()->json([
            'status' => 200,
            'data' => $finalResult
        ]);
    }
    public function getClientsByType()
    {
        $clientsByType = $this->repository->getClients()
            ->with('clientType')
            ->get()
            ->filter(fn($client) => $client->clientType !== null)
            ->groupBy('client_type_id')
            ->map(function ($clients) {
                return [
                    'type_name' => $clients->first()->clientType->name,
                    'client_count' => $clients->count()
                ];
            });

        $sortedTypes = $clientsByType->sortByDesc('client_count');

        $topTypes = $sortedTypes->take(3)->values();

        return response()->json([
            'status' => 200,
            'data' => $topTypes->toArray()
        ]);
    }
    public function getClientsByGammut()
    {
        $clientsByGamut = $this->repository->getClients()
            ->with('gamut')
            ->get()
            ->filter(fn($client) => $client->gamut !== null)
            ->groupBy('gamut_id')
            ->map(function ($clients) {
                return [
                    'gamut_name' => $clients->first()->gamut->name,
                    'client_count' => $clients->count()
                ];
            });

        $sortedGamut = $clientsByGamut->sortByDesc('client_count');

        $gamut = $sortedGamut->take(4)->values();

        return response()->json([
            'status' => 200,
            'data' => $gamut->toArray()
        ]);
    }

}
