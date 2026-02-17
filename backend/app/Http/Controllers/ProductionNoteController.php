<?php

namespace App\Http\Controllers;

use App\Exports\ProductionNoteExport;
use App\Helpers\FilterHelper;
use App\Helpers\NumberHelper;
use App\Models\OrderNote;
use App\Models\OutputNoteItem;
use App\Models\ProductionNote;
use App\Models\ProductionNoteItem;
use App\Repositories\Contracts\DocumentItemRepositoryInterface;
use App\Repositories\Contracts\OutputNoteRepositoryInterface;
use App\Repositories\Contracts\ProductionNoteRepositoryInterface;
use App\Traits\HandlesConditionalRelationships;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;


class ProductionNoteController extends Controller
{
    use HandlesConditionalRelationships;

    protected $ProductionNoteRepository;
    protected $allItemRepository;
    protected $outputNoteRepository;



    public function __construct(ProductionNoteRepositoryInterface $ProductionNoteRepository, DocumentItemRepositoryInterface $allItemRepository, OutputNoteRepositoryInterface $outputNoteRepository)
    {
        $this->ProductionNoteRepository = $ProductionNoteRepository;
        $this->allItemRepository = $allItemRepository;
        $this->outputNoteRepository = $outputNoteRepository;
    }
    public function getProductionNotes(Request $request, ProductionNoteRepositoryInterface $repository)
    {
        try {
            $filters = [
                'user_id' => $request->input('user'),
                'quote_id' => $request->input('quote'),
                'client_id' => $request->input('client'),
                'created_at' => $request->input('date'),
                'search' => $request->input('search'),
                'archive' => $request->input('archive'),
                'status' => $request->input('status'),
            ];

            $perPage = $request->input('per_page', 10);
            if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
                $query = $repository->getProductionNotes()->with([
                    'client:id,legal_name',
                    'user:id,full_name',
                    'items'
                ])->onlyTrashed();
            } else {
                $query = $repository->getProductionNotes()->with([
                    'client:id,legal_name',
                    'user:id,full_name',
                    'items'
                ]);
            }


            $filterableFields = ['user_id', 'client_id', 'quote_id', 'status'];
            $searchableFields = ['code'];

            if (!empty($filters['search'])) {
                $searchQuery = $filters['search'];
                $query->where(function ($q) use ($searchQuery, $searchableFields) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'LIKE', "%$searchQuery%");
                    }
                });
            } else {
                $query = FilterHelper::applyFilters($query, $filters, $filterableFields);

                if (!empty($filters['created_at'])) {
                    $query->whereDate('created_at', $filters['created_at']);
                }
                if (!empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }
            }

            $query->orderBy('created_at', 'desc');
            $productionnotes = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'current_page' => $productionnotes->currentPage(),
                'total_production_notes' => $productionnotes->total(),
                'per_page' => $productionnotes->perPage(),
                'production_notes' => $productionnotes
            ]);
        } catch (\Exception $e) {
            $status = (int) $e->getCode();
            if ($status < 100 || $status > 599) {
                $status = 500;
            }

            return response()->json([
                'status' => $status,
                'message' => $e->getMessage(),
            ], $status);
        }
    }


    public function getProductionNoteById(Request $request, $id)
    {
        try {
            $isArchived = filter_var($request->query('archive'), FILTER_VALIDATE_BOOLEAN);

            $baseRelationships = [
                'client:id,legal_name,ice',
                'user:id,code,full_name',
                'items' => function ($query) {
                    $query->select("*")->orderBy('order');
                },
                // 'refunds' => function ($query) {
                //     $query->select('id', 'status', 'code', 'tax_amount', 'quote_id', 'is_taxable');
                // },
                'orderNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                },
                'quoterequests' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                },
                'outputNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                },
                'deliveryNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                },
                'quotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'is_taxable');
                },
                // 'invoices' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                // },
                // 'orderReceipts' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                // },
                // 'invoiceCredits' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                // },
                // 'returnNotes' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                // },
                'productionNotes' => function ($query) {
                    $query->select('id','client_id',  'status', 'code', 'quote_id', 'is_taxable')
                    ->with([
                        'client:id,legal_name,ice,balance',
                    ])
                    ->orderBy('code');
                }
            ];

            $conditionalRelationships = [
                'invoices' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable', 'process_group_id');
                },
                'orderReceipts' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable', 'process_group_id');
                },
                'returnNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable', 'process_group_id');
                },
                'invoiceCredits' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable', 'process_group_id');
                },
                'refunds' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable', 'process_group_id');
                },
            ];
            // Merge relationships (this automatically handles the dual loading)
            $relationships = $this->buildRelationshipsWithConditional(
                $baseRelationships,
                $conditionalRelationships
            );


            if ($isArchived) {
                $productionNote = $this->ProductionNoteRepository->getProductionNotes()->onlyTrashed()->with($relationships)->find($id);
            } else {
                $productionNote = $this->ProductionNoteRepository->getProductionNotes()->with($relationships)->find($id);
            }

            if (!$productionNote) {
                return response()->json(['message' => 'Note de production introuvable'], 404);
            }
            $productionNote = $this->mergeConditionalRelationships($productionNote);

            return response()->json($productionNote);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'is_taxable' => 'nullable|boolean',
                'status' => 'nullable|string|in:Brouillon,Validé,Annulé',
                'order_note_id' => 'nullable|exists:order_notes,id',
                'quote_id' => 'nullable|exists:quotes,id',
                'output_note_id' => 'nullable|exists:output_notes,id',
                'production_comment' => 'nullable|string|max:500',
                'code' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:255',

                'items' => 'required|array|size:1',
                'items.0.description' => 'nullable|string|max:255',
                'items.0.price' => 'required|numeric|min:0',
                'items.0.quantity' => 'required|numeric|min:0',
                'items.0.discount' => 'nullable|numeric|min:0|max:100',
                'items.0.undiscounted_amount' => 'nullable|numeric|min:0',
                'items.0.amount' => 'nullable|numeric|min:0',
                'items.0.order' => 'nullable|integer',
                'items.0.status' => 'nullable|string|in:Validé,Brouillon,Annulé',
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first(), 422);
            }
            $data = $request->all();
            $OrderNote = null;
            if (!empty($data['order_note_id'])) {
                $OrderNote = OrderNote::find($data['order_note_id']);
                if (!$OrderNote) {
                    return response()->json(['message' => 'Note de commande introuvable.'], 404);
                }
                if ($OrderNote->status !== 'Terminé') {
                    return response()->json(['message' => 'Impossible de créer un nouveau document. La note de commande actuelle n\'est pas "Validé".'], 400);
                }
            }
            if (isset($data['code']) && $data['code'] === $OrderNote->code) {
                unset($data['code']);
            } else if (isset($data['code'])) {
                $validator = Validator::make($data, [
                    'code' => 'unique:production_notes,code,' . ($data['id'] ?? 'null'),
                ]);

                if ($validator->fails()) {
                    return response()->json(['message' => 'Ce code est déjà utilisé par un autre document.'], 422);
                }
            }


            if (!isset($data['items']) || !is_array($data['items']) || count($data['items']) !== 1) {
                return response()->json(['message' => 'Un seul élément doit être fourni.'], 400);
            }

            $itemData = $data['items'][0];
            $itemData['discount'] = isset($itemData['discount']) && is_numeric($itemData['discount'])
                ? floatval($itemData['discount'])
                : 0;

            if (!isset($itemData['price'], $itemData['quantity'], $itemData['discount'])) {
                return response()->json(['message' => 'Champ prix, quantité ou remise manquant dans l\'élément.'], 400);
            }


            $itemData['undiscounted_amount'] = $itemData['price'] * $itemData['quantity'];
            $itemData['amount'] = $itemData['undiscounted_amount'] - $itemData['discount'];

            $authUser = Auth::user();

            $productionNoteData = [
                'amount' => $itemData['amount'],
                'order_note_id' => $data['order_note_id'] ?? null,
                'is_taxable' => $data['is_taxable'] ?? false,
                'tax_amount' => ($data['is_taxable'] ?? false) ? $itemData['amount'] * 0.2 : 0,
                'final_amount' => $itemData['amount'] + (($data['is_taxable'] ?? false) ? $itemData['amount'] * 0.2 : 0),
                'total_phrase' => NumberHelper::convertNumberToWords(
                    $itemData['amount'] + (($data['is_taxable'] ?? false) ? $itemData['amount'] * 0.2 : 0)
                ),
                'status' => (!empty($data['status'])) ? $data['status'] : 'Brouillon',
                'client_id' => $OrderNote->client_id ?? null,
                'user_id' => $authUser ? $authUser->id : null,
                'quote_id' => $OrderNote->quote_id ?? null,
            ];

            $productionNote = $this->ProductionNoteRepository->create($productionNoteData);
            if (!isset($data['code'])) {
                $duplicateCodeExists = ProductionNote::where('code', $productionNote->code)
                    ->where('id', '!=', $productionNote->id)
                    ->exists();

                if ($duplicateCodeExists) {
                    $this->ProductionNoteRepository->delete($productionNote->id);
                    return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
                }
            }

            $itemData['production_note_id'] = $productionNote->id;
            $itemData['description'] = $itemData['description'] ?? 'No description provided';


            $this->allItemRepository
                ->setModel(ProductionNoteItem::class)
                ->create(['type' => 'productionnoteitem'] + $itemData);

            return response()->json([
                'production_note' => $productionNote,
                'item' => $itemData,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Échec de la création du document.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function duplicate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:production_notes,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()], 422);
            }

            $productionNoteId = $request->input('id');

            $originalProductionNote = $this->ProductionNoteRepository->getProductionNotes()->with([
                'items' => function ($query) {
                    $query->select("*")->orderBy('order');
                }
            ])->find($productionNoteId);

            if (!$originalProductionNote) {
                return response()->json(['message' => 'Note de production introuvable.'], 404);
            }

            $productionNoteData = $originalProductionNote->toArray();

            unset($productionNoteData['id']);
            unset($productionNoteData['created_at']);
            unset($productionNoteData['updated_at']);
            unset($productionNoteData['code']);
            unset($productionNoteData['process_group_id']);

            $productionNoteData['status'] = 'Brouillon';

            $authUser = Auth::user();
            $productionNoteData['user_id'] = $authUser->id;

            $items = $productionNoteData['items'];
            unset($productionNoteData['items']);

            $newProductionNote = $this->ProductionNoteRepository->create($productionNoteData);

            if ($this->ProductionNoteRepository->codeExists($newProductionNote->code, $newProductionNote->id)) {
                $this->ProductionNoteRepository->delete($newProductionNote->id);
                return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
            }
            $newProductionNoteId = $newProductionNote->id;

            $newItems = [];
            foreach ($items as $itemData) {
                unset($itemData['id']);
                unset($itemData['created_at']);
                unset($itemData['updated_at']);


                $itemData['production_note_id'] = $newProductionNoteId;

                $newItem = $this->allItemRepository
                    ->setModel(ProductionNoteItem::class)
                    ->create(['type' => 'productionnoteitem'] + $itemData);

                $newItems[] = $newItem;
            }

            return response()->json([
                'message' => 'Note de production dupliquée avec succès',
                'id' => $newProductionNote->id,
                'items' => $newItems
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Échec de la duplication de la note de production',
                'error' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function updateStatus(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'status' => 'required|string',
            ]);

            $status = $request->input('status');
            $ids = $request->input('ids');

            $updatedQuotes = [];
            $errors = [];

            foreach ($ids as $id) {
                $quote = $this->ProductionNoteRepository->findById($id);

                if (!$quote) {
                    $errors[] = ['id' => $id, 'error' => 'Devis introuvable'];
                    continue;
                }

                if ($quote->status === 'Terminé') {
                    $errors[] = ['id' => $id, 'error' => 'Ce devis est déjà terminé et ne peut pas être modifié'];
                    continue;
                }

                $updated = $this->ProductionNoteRepository->update($id, ['status' => $status]);

                if (!$updated) {
                    $errors[] = ['id' => $id, 'error' => 'Échec de la mise à jour du statut'];
                    continue;
                }

                $updatedQuote = $this->ProductionNoteRepository->findById($id);
                $updatedQuotes[] = [
                    'id' => $updatedQuote->id,
                    'status' => $updatedQuote->status,
                ];
            }

            return response()->json([
                'message' => 'Mise à jour du statut terminée.',
                'updated' => $updatedQuotes,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour du statut.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();
            if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
                return response()->json(['message' => 'Pas d\'articles fournis. La note de retour doit contenir au moins un article.'], 422);
            }
            $productionnoteAmount = 0;

            $productionnote = $this->ProductionNoteRepository->findById($id);

            if (!$productionnote) {
                return response()->json(['message' => 'Note de production introuvable.'], 404);
            }
            if ($productionnote->status === 'Validé' || $productionnote->status === 'Annulé' || $productionnote->status === 'Terminé') {
                return response()->json(['message' => 'Ce Bon De Production ne peut pas être genéré'], 403);
            }
            $validator = Validator::make($data, [
                'code' => 'unique:production_notes,code,' . $id,
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()], 422);
            }
            $existingItems = $productionnote->items;

            $requestItemIds = isset($data['items']) ? array_column($data['items'], 'id') : [];

            $items = [];

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (isset($itemData['delete']) && $itemData['delete'] === true) {
                        if (isset($itemData['id'])) {
                            $this->allItemRepository
                                ->setModel(ProductionNoteItem::class)->delete($itemData['id']);
                        }
                        continue;
                    }

                    $itemData['discount'] = isset($itemData['discount']) && is_numeric($itemData['discount'])
                        ? floatval($itemData['discount'])
                        : 0;

                    $itemData['undiscounted_amount'] = $itemData['price'] * $itemData['quantity'];
                    $itemData['amount'] = $itemData['undiscounted_amount'] - $itemData['discount'];

                    $productionnoteAmount += $itemData['amount'];

                    if (isset($itemData['id']) && $itemData['id'] !== null && $itemData['id'] !== '') {
                        $this->allItemRepository
                            ->setModel(ProductionNoteItem::class)->update($itemData['id'], $itemData);
                    } else {
                        $itemData['productionnote_id'] = $id;
                        $this->allItemRepository
                            ->setModel(ProductionNoteItem::class)->create($itemData);
                    }

                    $items[] = $itemData;
                }
            }

            $itemsToDelete = array_diff(array_column($existingItems->toArray(), 'id'), $requestItemIds);

            foreach ($itemsToDelete as $itemId) {
                $this->allItemRepository
                    ->setModel(ProductionNoteItem::class)->delete($itemId);
            }

            $data['amount'] = $productionnoteAmount;
            $data['tax_amount'] = $data['is_taxable'] ? $productionnoteAmount * 0.2 : 0;
            $data['final_amount'] = $productionnoteAmount + $data['tax_amount'];
            $data['total_phrase'] = NumberHelper::convertNumberToWords($data['final_amount']);

            $data = array_map(function ($value) {
                // Only convert truly empty values to null
                if ($value === '' || (empty($value) && $value !== 0 && $value !== '0' && $value !== false)) {
                    return null;
                }
                return $value;
            }, $data);



            $productionnote = $this->ProductionNoteRepository->update($id, $data);

            if (!$productionnote) {
                return response()->json(['message' => 'ProductionNote update failed'], 500);
            }

            return response()->json(['productionnote' => $productionnote, 'items' => $items]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Une erreur est survenue lors de la mise à jour de la note de production.', 'error' => $e->getMessage()], 500);
        }
    }



    public function delete($id)
    {
        try {
            $productionnote = $this->ProductionNoteRepository->findById($id);

            if (!$productionnote) {
                return response()->json(['message' => 'Note de production introuvable'], 404);
            }


            foreach ($productionnote->items as $item) {
                $this->allItemRepository
                    ->setModel(ProductionNoteItem::class)->delete($item->id);
            }

            $deleted = $this->ProductionNoteRepository->delete($id);

            if ($deleted) {
                return response()->json(['message' => 'Note de production et éléments associés supprimés avec succès']);
            }

            return response()->json(['message' => 'Échec de la suppression de la note de production.'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Une erreur est survenue lors de la suppression de la note de production.', 'error' => $e->getMessage()], 500);
        }
    }
    public function exportProductionNotes(Request $request)
    {
        try {
            $filters = [
                'user_id' => $request->input('user'),
                'client_id' => $request->input('client'),
                'created_at' => $request->input('date'),
                'search' => $request->input('search'),
                'status' => $request->input('status'),
            ];

            $perPage = $request->input('per_page', 10);

            $fileName = 'production_notes_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
            $filePath = storage_path('app/public/' . $fileName);

            $export = new ProductionNoteExport($filters, $perPage);

            Excel::store($export, 'public/' . $fileName);

            $downloadUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'Notes de production exportées avec succès.',
                'download_url' => $downloadUrl,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Échec de l\'exportation des notes de production : ' . $e->getMessage(),
            ], 500);
        }
    }

public function generateDocument(Request $request)
{
    try {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['message' => 'Aucune note de production sélectionnée.'], 400);
        }

        $allItems = [];
        $outputNote = null;
        $totalProductionAmount = 0;
        $isTaxable = false;
        $quoteId = null;
        $clientId = null;
        $outputComment = null;
        $validProductionNotes = [];

        foreach ($ids as $id) {
            $productionnote = $this->ProductionNoteRepository
                ->getProductionNotes()
                ->with(['client', 'user', 'items'])
                ->find($id);

            if (!$productionnote) {
                return response()->json(['message' => "Note de production avec l'ID {$id} introuvable."], 404);
            }
            if ($productionnote->status === "Terminé") {
                return response()->json(['message' => 'Note de production Terminé'], 404);
            }
            if ($productionnote->status !== 'Validé') {
                return response()->json(['message' => "Bon de production n'est pas validé."], 400);
            }

            $validProductionNotes[] = $productionnote;

            if ($outputNote === null) {
                $isTaxable = $productionnote->is_taxable;
                $quoteId = $productionnote->quote_id;
                $clientId = $productionnote->client_id;
                $outputComment = $productionnote->production_comment;
            }

            $productionNoteAmount = 0;
            $productionNoteItems = $productionnote->items->map(function ($item) use (&$productionNoteAmount) {
                $discount = max(0, $item->discount ?? 0);

                $undiscountedAmount = $item->price * $item->quantity;
                $itemAmount = $undiscountedAmount - $discount;

                $productionNoteAmount += $itemAmount;

                return [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'production_note_id' => $item->production_note_id,
                    'order' => $item->order,
                    'discount' => $discount,
                    'undiscounted_amount' => $undiscountedAmount,
                    'amount' => $itemAmount,
                ];
            });

            $totalProductionAmount += $productionNoteAmount;

            foreach ($productionNoteItems as $itemData) {
                $outputItemData = array_merge($itemData, [
                    'output_note_id' => null,
                    'production_note_id' => $productionnote->id
                ]);

                $allItems[] = $outputItemData;
            }
        }

        $taxAmount = $isTaxable ? $totalProductionAmount * 0.2 : 0;
        $finalAmount = $totalProductionAmount + $taxAmount;

        $authUser = Auth::user();
        $data = [
            'quote_id' => $quoteId,
            'amount' => $totalProductionAmount,
            'tax_amount' => $taxAmount,
            'final_amount' => $finalAmount,
            'total_phrase' => NumberHelper::convertNumberToWords($finalAmount),
            'client_id' => $clientId,
            'is_taxable' => $isTaxable,
            'status' => 'Brouillon',
            'production_note_id' => null,
            'output_comment' => $outputComment,
            'user_id' => $authUser ? $authUser->id : null
        ];

        if (!empty($allItems)) {
            // Create the output note - repository handles unique code generation
            $outputNote = $this->outputNoteRepository->create($data);

            // Create items for the output note
            foreach ($allItems as &$item) {
                $item['output_note_id'] = $outputNote->id;
                $this->allItemRepository
                    ->setModel(OutputNoteItem::class)
                    ->create($item);
            }

            // Update production note statuses to "Terminé"
            foreach ($validProductionNotes as $productionnote) {
                $this->ProductionNoteRepository->update($productionnote->id, ["status" => "Terminé",
                "output_note_id" => $outputNote->id ]);


            }
        } else {
            return response()->json(['message' => 'Aucun élément valide pour créer une note de sortie.'], 400);
        }

        return response()->json([
            'output_note' => $outputNote,
            'items' => $allItems,
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'status' => $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 500,
            'message' => $e->getMessage(),
        ], $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 500);
    }
}



    public function generatePdf($id)
    {
        try {

            $productionNote = $this->ProductionNoteRepository
                ->getProductionNotes()
                ->with(['client', 'user', 'items'])
                ->find($id);

            if (!$productionNote) {
                return response()->json(['message' => 'Note de production introuvable.'], 404);
            }

            $data = [
                'id' => $productionNote->id,
                'code' => $productionNote->code,
                'status' => $productionNote->status,
                'production_note' => $productionNote->production_note,
                'amount' => $productionNote->amount,
                'is_taxable' => $productionNote->is_taxable,
                'tax_amount' => $productionNote->tax_amount,
                'final_amount' => $productionNote->final_amount,
                'client_ice' => $productionNote->client->ice ?? null,
                'total_phrase' => $productionNote->total_phrase,
                'production_comment' => $productionNote->production_comment,
                'client' => $productionNote->client->legal_name ?? null,
                'user' => $productionNote->user->full_name ?? null,
                'items' => $productionNote->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'discount' => $item->discount,
                        'amount' => $item->amount,
                    ];
                })->toArray(),
            ];

            $pdf = Pdf::loadView('pdf.production_note', ['productionNote' => $data]);

            $fileName = 'production_note_' . $productionNote->code . '_' . time() . '.pdf';

            $pdf->save(storage_path('app/public/' . $fileName));

            $publicUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'PDF généré avec succès.',
                'download_url' => $publicUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la génération du PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
