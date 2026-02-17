<?php

namespace App\Http\Controllers;

use App\Exports\DeliveryNoteExport;
use App\Helpers\FilterHelper;
use App\Helpers\NumberHelper;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\InvoiceItem;
use App\Models\InvoiceQuote;
use App\Models\OrderReceiptItem;
use App\Models\OrderReceiptQuote;
use App\Models\OutputNote;
use App\Models\ReturnNoteItem;
use App\Models\ReturnNoteQuote;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Contracts\DeliveryNoteRepositoryInterface;
use App\Repositories\Contracts\DocumentItemRepositoryInterface;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\OrderReceiptRepositoryInterface;
use App\Repositories\Contracts\ProductionNoteRepositoryInterface;
use App\Repositories\Contracts\ReturnNoteRepositoryInterface;
use App\Traits\HandlesConditionalRelationships;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DeliveryNoteController extends Controller
{
    use HandlesConditionalRelationships;

    protected $deliveryNoteRepository;
    protected $allItemRepository;
    protected $invoiceRepository;
    protected $returnNoteRepository;
    protected $orderReceiptRepository;
    protected $ProductionNoteRepository;


    public function __construct(DeliveryNoteRepositoryInterface $deliveryNoteRepository, DocumentItemRepositoryInterface $allItemRepository, InvoiceRepositoryInterface $invoiceRepository, ReturnNoteRepositoryInterface $returnNoteRepository, OrderReceiptRepositoryInterface $orderReceiptRepository, ProductionNoteRepositoryInterface $ProductionNoteRepository,)
    {
        $this->deliveryNoteRepository = $deliveryNoteRepository;
        $this->allItemRepository = $allItemRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->returnNoteRepository = $returnNoteRepository;
        $this->orderReceiptRepository = $orderReceiptRepository;
        $this->ProductionNoteRepository = $ProductionNoteRepository;
    }


    public function getDeliveryNotes(Request $request)
    {
        $filters = [
            'user_id' => $request->input('user'),
            'client_id' => $request->input('client'),
            'created_at' => $request->input('create'),
            'quote_id' => $request->input('quote'),
            'archive' => $request->input('archive'),
            'status' => $request->input('status'),
            'is_taxable' => $request->input('tax'),
        ];

        $search = $request->input('search');

        $perPage = $request->input('per_page', 10);
        if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
            $query = $this->deliveryNoteRepository->getDeliveryNotes()->with([
                'client:id,legal_name',
                'user:id,full_name',
                'items'
            ])->onlyTrashed();
        } else {
            $query = $this->deliveryNoteRepository
                ->getDeliveryNotes()
                ->with([
                    'client:id,legal_name,ice',
                    'user:id,full_name',
                    'items' => function ($subQuery) {
                        $subQuery->select('*')->orderBy('order');
                    }
                ]);
        }

        $filterableFields = ['user_id', 'client_id', 'quote_id', 'status', 'is_taxable'];

        $searchableFields = ['code', 'total_phrase'];

        $query = FilterHelper::applyFilters($query, $filters, $filterableFields);
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if ($filters['is_taxable'] !== null && $filters['is_taxable'] !== '') {
            $query->where('is_taxable', $filters['is_taxable']);
        }

        $query = FilterHelper::applySearch($query, $search, $searchableFields);

        $query->orderBy('created_at', 'desc');

        $deliveryNotes = $query->paginate($perPage);

        return response()->json([
            'status' => 200,
            'current_page' => $deliveryNotes->currentPage(),
            'total_delivery_notes' => $deliveryNotes->total(),
            'per_page' => $deliveryNotes->perPage(),
            'delivery_notes' =>  $deliveryNotes
        ]);
    }

    public function getDeliveryNoteById(Request $request, $id)
    {
        try {
            $isArchived = filter_var($request->query('archive'), FILTER_VALIDATE_BOOLEAN);

            $baseRelationships  = [
                'client',
                'user:id,code,full_name',
                'items' => function ($query) {
                    $query->select("*")->orderBy('order');
                },
                // 'orderReceipts' => function ($query) {
                //     $query->select('id', 'status', 'code', "quote_id", "is_taxable");
                // },
                'quotes' => function ($query) {
                    $query->select('id', 'status', 'code', "is_taxable");
                },
                // 'invoiceCredits' => function ($query) {
                //     $query->select('id', 'status', 'code', "quote_id", "is_taxable");
                // },
                // 'returnNotes' => function ($query) {
                //     $query->select('id', 'status', 'code', "quote_id", "is_taxable");
                // },
                // 'invoices' => function ($query) {
                //     $query->select('id', 'status', 'code', "quote_id", "is_taxable","process_group_id");
                // },
                'outputNotes' => function ($query) {
                    $query->select('id', 'status', 'code', "quote_id", "is_taxable");
                },
                'orderNotes' => function ($query) {
                    $query->select('id', 'status', 'code', "quote_id", "is_taxable");
                },
                'productionNotes' => function ($query) {
                    $query->select('id', 'status', 'code', "quote_id", "is_taxable");
                },
                // 'refunds' => function ($query) {
                //     $query->select('id', 'status', 'code', "quote_id", "is_taxable");
                // },
                'quoterequests' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                },



                'deliveryNotes' => function ($query) {
                    $query->select('id', 'client_id', 'status', 'code', 'tax_amount', 'quote_id', "is_taxable", "amount", 'final_amount')
                        ->with([
                            'client:id,legal_name,ice,balance',
                            'items' => function ($subQuery) {
                                $subQuery->select('*')->orderBy('order');
                            }
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

            $query = $this->deliveryNoteRepository->getDeliveryNotes();

            if ($isArchived) {
                $query = $query->onlyTrashed();
            }

            $deliveryNote = $query->with($relationships)->find($id);

            if (!$deliveryNote) {
                throw new ModelNotFoundException('Bon de livraison introuvable');
            }

            $deliveryNote = $this->mergeConditionalRelationships($deliveryNote);

            return response()->json($deliveryNote);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'status' => 'required|string',
        ]);

        $status = $request->input('status');
        $ids = $request->input('ids');

        $updatedQuotes = [];
        $errors = [];

        foreach ($ids as $id) {
            $quote = $this->deliveryNoteRepository->findById($id);

            if (!$quote) {
                $errors[] = ['id' => $id, 'error' => 'Devis introuvable'];
                continue;
            }

            if ($quote->status === 'Terminé') {
                $errors[] = ['id' => $id, 'error' => 'Ce devis est déjà terminé et ne peut pas être modifié'];
                continue;
            }

            $updated = $this->deliveryNoteRepository->update($id, ['status' => $status]);

            if ($updated) {
                $updatedQuote = $this->deliveryNoteRepository->findById($id);
                $updatedQuotes[] = [
                    'id' => $updatedQuote->id,
                    'status' => $updatedQuote->status,
                ];
            } else {
                $errors[] = ['id' => $id, 'error' => 'Échec de la mise à jour du statut'];
            }
        }

        return response()->json([
            'message' => 'Mise à jour du statut terminée',
            'updated' => $updatedQuotes,
            'errors' => $errors,
        ]);
    }



    public function create(Request $request)
    {

        $data = $request->all();



        $OutputNote = OutputNote::with('items')->find($data['output_note_id']);
        if (!$OutputNote) {
            throw new ModelNotFoundException('output introuvable');
        }
        if ($OutputNote->status !== 'Validé') {
            throw new \Exception("Impossible de créer un nouveau document. Le statut actuel du document n'est pas 'Validé'.", 400);
        }

        $data['amount'] = $OutputNote->amount;
        $data['tax_amount'] = $OutputNote->tax_amount;
        $data['final_amount'] = $OutputNote->final_amount;
        $data['total_phrase'] = $OutputNote->total_phrase;
        $data['client_id'] = $OutputNote->client_id;
        $data['is_taxable'] = $OutputNote->is_taxable;
        $data['status'] = 'Brouillon';

        $authUser = Auth::user();
        $data['user_id'] = $authUser ? $authUser->id : null;
        $data = array_map(function ($value) {
            return ($value === '' || $value === null) ? null : $value;
        }, $data);
        $deliveryNote = $this->deliveryNoteRepository->create($data);
        if (!isset($data['code'])) {
            $duplicateCodeExists = DeliveryNote::where('code', $deliveryNote->code)
                ->where('id', '!=', $deliveryNote->id)
                ->exists();

            if ($duplicateCodeExists) {
                $this->deliveryNoteRepository->delete($deliveryNote->id);
                return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
            }
        }

        $items = $OutputNote->items->map(function ($item) use ($OutputNote) {
            return [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'discount' => $item->discount,
                'status' => 'Validé',
                'undiscounted_amount' => $item->undiscounted_amount,
                'amount' => $item->amount,
                'order_note_id' => $OutputNote->id,

            ];
        });

        foreach ($items as $itemData) {
            $this->allItemRepository
                ->setModel(DeliveryNoteItem::class)->create($itemData);
        }

        return response()->json(['delivery_note' => $deliveryNote, 'items' => $items], 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
            return response()->json(['message' => 'Pas d\'articles fournis. La note de retour doit contenir au moins un article.'], 422);
        }
        $deliveryNoteAmount = 0;

        $deliveryNote = $this->deliveryNoteRepository->findById($id);

        if (!$deliveryNote) {
            throw new ModelNotFoundException('Bon de livraison introuvable');
        }
        if ($deliveryNote->status === 'Validé' || $deliveryNote->status === 'Annulé' || $deliveryNote->status === 'Retourné' || $deliveryNote->status === 'Terminé') {
            return response()->json(['message' => 'Ce Bon de livraison ne peut pas être modifié'], 403);
        }

        if (isset($data['code']) && $data['code'] === $deliveryNote->code) {
            unset($data['code']);
        } else if (isset($data['code'])) {
            $validator = Validator::make($data, [
                'code' => 'unique:delivery_notes,code,' . $id,
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Ce code est déjà utilisé par un autre document.'], 422);
            }
        }

        $existingItems = $deliveryNote->items;

        $requestItemIds = isset($data['items']) ? array_column($data['items'], 'id') : [];

        $items = [];

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemData) {
                if (isset($itemData['delete']) && $itemData['delete'] === true) {
                    if (isset($itemData['id'])) {
                        $this->allItemRepository
                            ->setModel(DeliveryNoteItem::class)->delete($itemData['id']);
                    }
                    continue;
                }

                $itemData['discount'] = isset($itemData['discount']) && is_numeric($itemData['discount'])
                    ? floatval($itemData['discount'])
                    : 0;
                $itemData['status'] = 'Validé';
                $itemData['undiscounted_amount'] = $itemData['price'] * $itemData['quantity'];
                $itemData['amount'] = $itemData['undiscounted_amount'] - $itemData['discount'];

                $deliveryNoteAmount += $itemData['amount'];

                // ADDED: Sanitize item data to handle empty strings and null values properly
                $itemData = array_map(function ($value) {
                    // Convert empty strings to null, but preserve 0 and false
                    if ($value === '' || $value === null) {
                        return null;
                    }
                    return $value;
                }, $itemData);

                if (isset($itemData['id']) && $itemData['id'] !== null && $itemData['id'] !== '') {
                    $this->allItemRepository
                        ->setModel(DeliveryNoteItem::class)->update($itemData['id'], $itemData);
                } else {
                    $itemData['delivery_note_id'] = $id;
                    $this->allItemRepository
                        ->setModel(DeliveryNoteItem::class)->create($itemData);
                }

                $items[] = $itemData;
            }
        }

        $itemsToDelete = array_diff(array_column($existingItems->toArray(), 'id'), $requestItemIds);

        foreach ($itemsToDelete as $itemId) {
            $this->allItemRepository
                ->setModel(DeliveryNoteItem::class)->delete($itemId);
        }

        // CHANGED: Store items separately before sanitizing
        $itemsData = $data['items'];
        unset($data['items']);

        $data['amount'] = $deliveryNoteAmount;
        $data['tax_amount'] = isset($data['is_taxable']) && $data['is_taxable'] ? $deliveryNoteAmount * 0.2 : 0;
        $data['final_amount'] = $deliveryNoteAmount + $data['tax_amount'];
        $data['total_phrase'] = NumberHelper::convertNumberToWords($data['final_amount']);

        // CHANGED: Better sanitization that preserves 0 and false
        $data = array_map(function ($value) {
            // Only convert truly empty values to null
            if ($value === '' || (empty($value) && $value !== 0 && $value !== '0' && $value !== false)) {
                return null;
            }
            return $value;
        }, $data);

        $deliveryNote = $this->deliveryNoteRepository->update($id, $data);

        return response()->json(['deliverynote' => $deliveryNote, 'items' => $items]);
    }

    public function delete($id)
    {
        $deliveryNote = $this->deliveryNoteRepository->findById($id);

        if (!$deliveryNote) {
            throw new ModelNotFoundException('Bon de livraison introuvable');
        }

        foreach ($deliveryNote->items as $item) {
            $this->allItemRepository
                ->setModel(DeliveryNoteItem::class)->delete($item->id);
        }

        $deleted = $this->deliveryNoteRepository->delete($id);

        if (!$deleted) {
            throw new \Exception('Échec de la suppression du bon de livraison');
        }

        return response()->json(['message' => 'document supprimés avec succès']);
    }

    public function exportDeliveryNotes(Request $request)
    {
        $filters = [
            'user_id' => $request->input('user'),
            'client_id' => $request->input('client'),
            'created_at' => $request->input('create'),
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'is_taxable' => $request->input('tax'),
        ];

        $perPage = $request->input('per_page', 10);

        $fileName = 'delivery_notes_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        $export = new DeliveryNoteExport($filters, $perPage);

        Excel::store($export, 'public/' . $fileName);

        $downloadUrl = asset('storage/' . $fileName);

        return response()->json([
            'status' => 200,
            'message' => 'Bons de livraison exportés avec succès.',
            'download_url' => $downloadUrl,
        ]);
    }
    public function generatePdf($id)
    {
        $deliveryNote = $this->deliveryNoteRepository->getDeliveryNotes()->with(['client', 'user', 'items'])->find($id);

        if (!$deliveryNote) {
            throw new ModelNotFoundException('Bon de livraison introuvable');
        }

        $deliveryNoteData = [
            'validity_date' => $deliveryNote->validity_date,
            'customer_name' => $deliveryNote->client->legal_name ?? 'N/A',
            'customer_address' => $deliveryNote->client->address ?? 'N/A',
            'total_in_words' => $deliveryNote->total_phrase,
            'comment' => $deliveryNote->delivery_comment,
            'total_ht' => $deliveryNote->amount,
            'discount' => $deliveryNote->discount ?? 0,
            'tva' => $deliveryNote->tax_amount,
            'total_ttc' => $deliveryNote->final_amount,
            'items' => $deliveryNote->items->map(function ($item) {
                return [
                    'designation' => $item->description,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'total' => $item->amount,
                ];
            })->toArray(),
        ];

        $pdf = Pdf::loadView('pdf.delivery_note', ['deliveryNote' => $deliveryNoteData]);

        $fileName = 'delivery_note_' . $deliveryNote->id . '_' . time() . '.pdf';

        return $pdf->download($fileName);
    }

    public function updateItemsStatus(Request $request, $id)
    {
        $data = $request->all();

        $deliveryNote = $this->deliveryNoteRepository->findById($id);

        if (!$deliveryNote) {
            throw new ModelNotFoundException('Bon de livraison introuvable');
        }

        if (in_array($deliveryNote->status, ['Validé', 'Annulé', 'Retourné'])) {
            return response()->json(['message' => 'Ce Bon de livraison ne peut pas être modifié'], 403);
        }



        DB::beginTransaction();

        try {
            $itemData = $data['item'];

            $item = $this->allItemRepository
                ->setModel(DeliveryNoteItem::class)
                ->findById($itemData['id']);

            if (!$item || $item->delivery_note_id != $deliveryNote->id) {
                DB::rollBack();
                return response()->json(['message' => 'Un élément ne fait pas partie de ce Bon de livraison'], 400);
            }

            $updateData = ['status' => $itemData['status']];

            $this->allItemRepository
                ->setModel(DeliveryNoteItem::class)
                ->update($itemData['id'], $updateData);

            $this->ProductionNoteRepository->update($item->production_note_id, $updateData);

            DB::commit();

            $deliveryNote = $this->deliveryNoteRepository->findById($id);

            return response()->json([
                'message' => 'Statut de l\'article mis à jour avec succès',
                'deliverynote' => $deliveryNote,
                'updated_item' => array_merge($item->toArray(), $updateData)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Une erreur est survenue lors de la mise à jour', 'error' => $e->getMessage()], 500);
        }
    }


    public function generateDocument(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json(['message' => 'Aucun bon de livraison sélectionné'], 400);
            }

            $allValidItems = [];
            $isTaxable = null;
            $quoteId = null;
            $clientId = null;
            $deliveryComment = null;
            $returnNote = [];
            $returnNoteItems = [];
            $totalReturnAmount = 0;
            $totalValidAmount = 0;

            foreach ($ids as $id) {
                $deliveryNote = $this->deliveryNoteRepository->getDeliveryNotes()->with(['client', 'user', 'items'])->find($id);

                if ($deliveryNote->status === "Terminé") {
                    return response()->json(['message' => 'Bon de livraison Terminé '], 404);
                }

                if (!$deliveryNote) {
                    return response()->json(['message' => 'Bon de livraison introuvable'], 404);
                }

                // NEW VALIDATION LOGIC
                // Check for inconsistencies between delivery note status and item statuses
                $hasValidItems = false;
                $hasARefaireItems = false;
                $hasRetourneItems = false;

                foreach ($deliveryNote->items as $item) {
                    if ($item->status === 'Validé') {
                        $hasValidItems = true;
                    } elseif ($item->status === 'A refaire') {
                        $hasARefaireItems = true;
                    } elseif ($item->status === 'Retourné') {
                        $hasRetourneItems = true;
                    }
                }

                // Rule 1: If status is "Rejeté" and ALL items are "Validé", do not generate
                if ($deliveryNote->status === 'Rejeté' && $hasValidItems && !$hasARefaireItems && !$hasRetourneItems) {
                    return response()->json(['message' => 'Les documents sélectionnés sont incohérents, merci de vérifier les statuts des documents et des articles'], 400);
                }

                // Rule 2: If status is "Validé" and ALL items are problematic, stop generation
                if ($deliveryNote->status === 'Validé' && !$hasValidItems && ($hasARefaireItems || $hasRetourneItems)) {
                    return response()->json(['message' => 'Les documents sélectionnés sont incohérents, merci de vérifier les statuts des documents et des articles'], 400);
                }

                if ($isTaxable === null) {
                    $isTaxable = $deliveryNote->is_taxable;
                    $quoteId = $deliveryNote->quote_id;
                    $clientId = $deliveryNote->client_id;
                    $deliveryComment = $deliveryNote->delivery_comment;
                } else if ($isTaxable !== $deliveryNote->is_taxable) {
                    return response()->json(['message' => 'Les bons de livraison ont des statuts fiscaux différents'], 400);
                }

                $validItems = [];
                $returnedItems = [];
                $redoItems = [];

                foreach ($deliveryNote->items as $item) {
                    $discount = max(0, $item->discount ?? 0);
                    $undiscountedAmount = $item->price * $item->quantity;
                    $itemAmount = $undiscountedAmount - $discount;

                    $item->delivery_note_id = $id;

                    $item->undiscounted_amount = $undiscountedAmount;
                    $item->amount = $itemAmount;

                    if ($item->status === 'Validé') {
                        $validItems[] = $item;
                        $allValidItems[] = $item;
                        $totalValidAmount += $itemAmount;
                    } elseif ($item->status === 'Retourné') {
                        $returnedItems[] = $item;
                        $totalReturnAmount += $itemAmount;
                    } elseif ($item->status === 'A refaire') {
                        $redoItems[] = $item;
                        $totalReturnAmount += $itemAmount;
                    }
                }

                $returnableItems = array_merge($returnedItems, $redoItems);
                if (!empty($returnableItems)) {

                    $returnableAmount = 0;
                    $mappedReturnableItems = array_map(function ($item) use (&$returnableAmount) {
                        $discount = max(0, $item->discount ?? 0);
                        $undiscountedAmount = $item->price * $item->quantity;
                        $itemAmount = $undiscountedAmount - $discount;
                        $returnableAmount += $itemAmount;

                        return [
                            'description' => $item->description,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'order' => $item->order,
                            'discount' => $discount,
                            'undiscounted_amount' => $undiscountedAmount,
                            'amount' => $itemAmount,
                            'production_note_id' => $item->production_note_id,

                        ];
                    }, $returnableItems);

                    $returnableTaxAmount = $deliveryNote->is_taxable ? $returnableAmount * 0.2 : 0;
                    $returnableFinalAmount = $returnableAmount + $returnableTaxAmount;

                    $returnNoteData = [
                        'quote_id' => $deliveryNote->quote_id,
                        'amount' => $returnableAmount,
                        'tax_amount' => $returnableTaxAmount,
                        'final_amount' => $returnableFinalAmount,
                        'total_phrase' => NumberHelper::convertNumberToWords($returnableFinalAmount),
                        'client_id' => $deliveryNote->client_id,
                        'is_taxable' => $deliveryNote->is_taxable,
                        'status' => 'Brouillon',
                        'return_comment' => $deliveryNote->delivery_comment,
                        'user_id' => Auth::id(),
                        'delivery_note_id' => $id,

                    ];

                    if (!empty($mappedReturnableItems)) {
                        $returnNote = $this->returnNoteRepository->create($returnNoteData);
                    }

                    $returnNoteItems = [];

                    foreach ($mappedReturnableItems as $itemData) {
                        $itemData['return_note_id'] = $returnNote->id;
                        $returnNoteItem = $this->allItemRepository->setModel(ReturnNoteItem::class)->create($itemData);
                        $returnNoteItems[] = $returnNoteItem;
                    }
                }
                $this->deliveryNoteRepository->update($id, ["status" => "Terminé"]);
            }


            $response = [
                'return_note' => $returnNote,
                'return_note_items' => $returnNoteItems
            ];

            if (!empty($allValidItems)) {

                $validAmount = 0;
                $mappedValidItems = array_map(function ($item) use (&$validAmount) {
                    $discount = max(0, $item->discount ?? 0);
                    $undiscountedAmount = $item->price * $item->quantity;
                    $itemAmount = $undiscountedAmount - $discount;
                    $validAmount += $itemAmount;

                    return [
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'order' => $item->order,
                        'discount' => $discount,
                        'undiscounted_amount' => $undiscountedAmount,
                        'amount' => $itemAmount,
                        'delivery_note_id' => $item->delivery_note_id,
                        'production_note_id' => $item->production_note_id,

                    ];
                }, $allValidItems);

                $validTaxAmount = $isTaxable ? $validAmount * 0.2 : 0;
                $validFinalAmount = $validAmount + $validTaxAmount;

                if ($isTaxable == 1) {
                    $invoiceData = [
                        'quote_id' => $quoteId,
                        'amount' => $validAmount,
                        'tax_amount' => $validTaxAmount,
                        'final_amount' => $validFinalAmount,
                        'total_phrase' => $validFinalAmount,
                        'client_id' => $clientId,
                        'is_taxable' => 1,
                        'status' => 'Brouillon',
                        'invoice_comment' => $deliveryComment,
                        'user_id' => Auth::id(),
                    ];

                    if (!empty($mappedValidItems)) {
                        $invoice = $this->invoiceRepository->create($invoiceData);

                        if (!$invoice) {
                            return response()->json(['message' => 'Échec de la création de la facture'], 500);
                        }
                    } else {
                        return response()->json(['message' => 'Aucun article valide pour créer une facture'], 400);
                    }
                    $invoiceItems = [];

                    foreach ($mappedValidItems as $itemData) {
                        $itemData['invoice_id'] = $invoice->id;
                        $itemData['status'] = 'Validé';
                        $invoiceItem = $this->allItemRepository->setModel(InvoiceItem::class)->create($itemData);
                        $invoiceItems[] = $invoiceItem;
                    }

                    $response['invoice'] = $invoice;
                    $response['invoice_items'] = $invoiceItems;

                    foreach ($ids as $deliveryNoteId) {
                        $this->deliveryNoteRepository->update($deliveryNoteId, [
                            'status' => 'Terminé',
                            'invoice_id' => $invoice->id
                        ]);
                    }
                } else {
                    $authUser = Auth::user();
                    $orderReceiptData = [
                        'quote_id' => $quoteId,
                        'amount' => $validAmount,
                        'tax_amount' => $validTaxAmount,
                        'final_amount' => $validFinalAmount,
                        'total_phrase' => NumberHelper::convertNumberToWords($validFinalAmount),
                        'client_id' => $clientId,
                        'is_taxable' => 0,
                        'status' => 'Brouillon',
                        'receipt_comment' => $deliveryComment,
                        'user_id' => $authUser->id,

                    ];
                    if (!empty($mappedValidItems)) {
                        $orderReceipt = $this->orderReceiptRepository->create($orderReceiptData);
                    }
                    $orderReceiptItems = [];

                    foreach ($mappedValidItems as $itemData) {
                        $itemData['order_receipt_id'] = $orderReceipt->id;
                        $itemData['status'] = 'Validé';
                        $orderReceiptItem = $this->allItemRepository->setModel(OrderReceiptItem::class)->create($itemData);
                        $orderReceiptItems[] = $orderReceiptItem;
                    }

                    $response['order_receipt'] = $orderReceipt;
                    $response['order_receipt_items'] = $orderReceiptItems;

                    foreach ($ids as $deliveryNoteId) {
                        $this->deliveryNoteRepository->update($deliveryNoteId, [
                            'status' => 'Terminé',
                            'order_receipt_id' => $orderReceipt->id
                        ]);
                    }
                }
            }

            return response()->json($response, 201);
        } catch (\Exception $e) {
            $statusCode = is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }


    public function generateDocumentDifferntDeliveryNote(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json(['message' => 'Aucun bon de livraison sélectionné'], 400);
            }

            $allValidItems = [];
            $allReturnableItems = [];
            $isTaxable = null;
            $clientId = null;
            $deliveryComment = null;
            $quoteIds = [];
            $deliveryNoteIdsWithReturns = [];

            foreach ($ids as $id) {
                $deliveryNote = $this->deliveryNoteRepository->getDeliveryNotes()->with(['client', 'user', 'items'])->find($id);

                if (!$deliveryNote) {
                    return response()->json(['message' => 'Bon de livraison introuvable'], 404);
                }

                if ($deliveryNote->status === "Terminé") {
                    return response()->json(['message' => 'Bon de livraison Terminé'], 404);
                }

                // Check for inconsistencies
                $hasValidItems = false;
                $hasARefaireItems = false;
                $hasRetourneItems = false;

                foreach ($deliveryNote->items as $item) {
                    if ($item->status === 'Validé') {
                        $hasValidItems = true;
                    } elseif ($item->status === 'A refaire') {
                        $hasARefaireItems = true;
                    } elseif ($item->status === 'Retourné') {
                        $hasRetourneItems = true;
                    }
                }

                // Rule 1: If status is "Rejeté" and ALL items are "Validé", do not generate
                if ($deliveryNote->status === 'Rejeté' && $hasValidItems && !$hasARefaireItems && !$hasRetourneItems) {
                    return response()->json(['message' => 'Les documents sélectionnés sont incohérents, merci de vérifier les statuts des documents et des articles'], 400);
                }

                // Rule 2: If status is "Validé" and ALL items are problematic, stop generation
                if ($deliveryNote->status === 'Validé' && !$hasValidItems && ($hasARefaireItems || $hasRetourneItems)) {
                    return response()->json(['message' => 'Les documents sélectionnés sont incohérents, merci de vérifier les statuts des documents et des articles'], 400);
                }

                // First delivery note sets the baseline
                if ($isTaxable === null) {
                    $isTaxable = $deliveryNote->is_taxable;
                    $clientId = $deliveryNote->client_id;
                    $deliveryComment = $deliveryNote->delivery_comment;
                } else {
                    // Validate same client
                    if ($clientId !== $deliveryNote->client_id) {
                        return response()->json(['message' => 'Les bons de livraison doivent appartenir au même client'], 400);
                    }
                    // Validate same tax status
                    if ($isTaxable !== $deliveryNote->is_taxable) {
                        return response()->json(['message' => 'Les bons de livraison ont des statuts fiscaux différents'], 400);
                    }
                }

                // Track quote IDs
                if (!in_array($deliveryNote->quote_id, $quoteIds)) {
                    $quoteIds[] = $deliveryNote->quote_id;
                }

                $returnedItems = [];
                $redoItems = [];

                foreach ($deliveryNote->items as $item) {
                    $discount = max(0, $item->discount ?? 0);
                    $undiscountedAmount = $item->price * $item->quantity;
                    $itemAmount = $undiscountedAmount - $discount;

                    $item->delivery_note_id = $id;
                    $item->undiscounted_amount = $undiscountedAmount;
                    $item->amount = $itemAmount;
                    $item->quote_id = $deliveryNote->quote_id;

                    if ($item->status === 'Validé') {
                        $allValidItems[] = $item;
                    } elseif ($item->status === 'Retourné') {
                        $returnedItems[] = $item;
                    } elseif ($item->status === 'A refaire') {
                        $redoItems[] = $item;
                    }
                }

                // Collect returnable items from this delivery note
                $returnableItems = array_merge($returnedItems, $redoItems);
                if (!empty($returnableItems)) {
                    $deliveryNoteIdsWithReturns[] = $id;
                    foreach ($returnableItems as $item) {
                        $allReturnableItems[] = $item;
                    }
                }
            }

            // ✅ Generate process_group_id ONLY if multiple quotes are involved
            $processGroupId = count($quoteIds) > 1 ? \Illuminate\Support\Str::uuid()->toString() : null;

            // ✅ If process_group_id is generated, update ALL related documents retroactively
            if ($processGroupId) {
                foreach ($quoteIds as $quoteId) {
                    // Update all documents in the chain for this quote_id
                    \App\Models\QuoteRequest::where('quote_id', $quoteId)->update(['process_group_id' => $processGroupId]);
                    \App\Models\Quote::where('id', $quoteId)->update(['process_group_id' => $processGroupId]);
                    \App\Models\OrderNote::where('quote_id', $quoteId)->update(['process_group_id' => $processGroupId]);
                    \App\Models\ProductionNote::where('quote_id', $quoteId)->update(['process_group_id' => $processGroupId]);
                    \App\Models\OutputNote::where('quote_id', $quoteId)->update(['process_group_id' => $processGroupId]);
                    \App\Models\DeliveryNote::where('quote_id', $quoteId)->update(['process_group_id' => $processGroupId]);
                }
            }

            $response = [];

            // Create ONE return note for ALL returnable items from all delivery notes
            if (!empty($allReturnableItems)) {
                $returnableAmount = 0;
                $mappedReturnableItems = array_map(function ($item) use (&$returnableAmount) {
                    $discount = max(0, $item->discount ?? 0);
                    $undiscountedAmount = $item->price * $item->quantity;
                    $itemAmount = $undiscountedAmount - $discount;
                    $returnableAmount += $itemAmount;

                    return [
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'order' => $item->order,
                        'discount' => $discount,
                        'undiscounted_amount' => $undiscountedAmount,
                        'amount' => $itemAmount,
                        'production_note_id' => $item->production_note_id,
                        'delivery_note_id' => $item->delivery_note_id,
                    ];
                }, $allReturnableItems);

                $returnableTaxAmount = $isTaxable ? $returnableAmount * 0.2 : 0;
                $returnableFinalAmount = $returnableAmount + $returnableTaxAmount;

                // Determine quote_id for return note
                $returnNoteQuoteId = count($quoteIds) === 1 ? $quoteIds[0] : null;

                $returnNoteData = [
                    'quote_id' => $returnNoteQuoteId,
                    'amount' => $returnableAmount,
                    'tax_amount' => $returnableTaxAmount,
                    'final_amount' => $returnableFinalAmount,
                    'total_phrase' => NumberHelper::convertNumberToWords($returnableFinalAmount),
                    'client_id' => $clientId,
                    'is_taxable' => $isTaxable,
                    'status' => 'Brouillon',
                    'return_comment' => $deliveryComment,
                    'user_id' => Auth::id(),
                    'process_group_id' => $processGroupId, // ✅ Add process_group_id
                ];

                $returnNote = $this->returnNoteRepository->create($returnNoteData);

                // Create pivot entries if there are multiple quotes
                if (count($quoteIds) > 1) {
                    foreach ($quoteIds as $quoteId) {
                        ReturnNoteQuote::create([
                            'return_note_id' => $returnNote->id,
                            'quote_id' => $quoteId
                        ]);
                    }
                }

                $returnNoteItems = [];
                foreach ($mappedReturnableItems as $itemData) {
                    $itemData['return_note_id'] = $returnNote->id;
                    $returnNoteItem = $this->allItemRepository->setModel(ReturnNoteItem::class)->create($itemData);
                    $returnNoteItems[] = $returnNoteItem;
                }

                $response['return_note'] = $returnNote;
                $response['return_note_items'] = $returnNoteItems;
                if (count($quoteIds) > 1) {
                    $response['return_note_quotes'] = $quoteIds;
                }

                foreach ($ids as $deliveryNoteId) {
                    $this->deliveryNoteRepository->update($deliveryNoteId, [
                        'status' => 'Terminé',
                        'return_note_id' => $returnNote->id
                    ]);
                }
            }

            // Generate Invoice or Order Receipt for valid items
            if (!empty($allValidItems)) {
                $validAmount = 0;
                $mappedValidItems = array_map(function ($item) use (&$validAmount) {
                    $discount = max(0, $item->discount ?? 0);
                    $undiscountedAmount = $item->price * $item->quantity;
                    $itemAmount = $undiscountedAmount - $discount;
                    $validAmount += $itemAmount;

                    return [
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'order' => $item->order,
                        'discount' => $discount,
                        'undiscounted_amount' => $undiscountedAmount,
                        'amount' => $itemAmount,
                        'delivery_note_id' => $item->delivery_note_id,
                        'production_note_id' => $item->production_note_id,
                        'quote_id' => $item->quote_id,
                    ];
                }, $allValidItems);

                $validTaxAmount = $isTaxable ? $validAmount * 0.2 : 0;
                $validFinalAmount = $validAmount + $validTaxAmount;

                // Determine quote_id - use single quote if all are the same, otherwise null
                $finalQuoteId = count($quoteIds) === 1 ? $quoteIds[0] : null;

                if ($isTaxable == 1) {
                    // Create Invoice
                    $invoiceData = [
                        'quote_id' => $finalQuoteId,
                        'amount' => $validAmount,
                        'tax_amount' => $validTaxAmount,
                        'final_amount' => $validFinalAmount,
                        'total_phrase' => NumberHelper::convertNumberToWords($validFinalAmount),
                        'client_id' => $clientId,
                        'is_taxable' => 1,
                        'status' => 'Brouillon',
                        'invoice_comment' => $deliveryComment,
                        'user_id' => Auth::id(),
                        'process_group_id' => $processGroupId, // ✅ Add process_group_id
                    ];

                    $invoice = $this->invoiceRepository->create($invoiceData);

                    if (!$invoice) {
                        return response()->json(['message' => 'Échec de la création de la facture'], 500);
                    }

                    // Only create pivot entries if there are multiple quotes
                    if (count($quoteIds) > 1) {
                        foreach ($quoteIds as $quoteId) {
                            InvoiceQuote::create([
                                'invoice_id' => $invoice->id,
                                'quote_id' => $quoteId
                            ]);
                        }
                    }

                    $invoiceItems = [];
                    foreach ($mappedValidItems as $itemData) {
                        $itemData['invoice_id'] = $invoice->id;
                        $itemData['status'] = 'Validé';
                        $invoiceItem = $this->allItemRepository->setModel(InvoiceItem::class)->create($itemData);
                        $invoiceItems[] = $invoiceItem;
                    }

                    $response['invoice'] = $invoice;
                    $response['invoice_items'] = $invoiceItems;
                    if (count($quoteIds) > 1) {
                        $response['invoice_quotes'] = $quoteIds;
                    }

                    foreach ($ids as $deliveryNoteId) {
                        $this->deliveryNoteRepository->update($deliveryNoteId, [
                            'status' => 'Terminé',
                            'invoice_id' => $invoice->id
                        ]);
                    }
                } else {
                    // Create Order Receipt
                    $orderReceiptData = [
                        'quote_id' => $finalQuoteId,
                        'amount' => $validAmount,
                        'tax_amount' => $validTaxAmount,
                        'final_amount' => $validFinalAmount,
                        'total_phrase' => NumberHelper::convertNumberToWords($validFinalAmount),
                        'client_id' => $clientId,
                        'is_taxable' => 0,
                        'status' => 'Brouillon',
                        'receipt_comment' => $deliveryComment,
                        'user_id' => Auth::id(),
                        'process_group_id' => $processGroupId, // ✅ Add process_group_id
                    ];

                    $orderReceipt = $this->orderReceiptRepository->create($orderReceiptData);

                    // Only create pivot entries if there are multiple quotes
                    if (count($quoteIds) > 1) {
                        foreach ($quoteIds as $quoteId) {
                            OrderReceiptQuote::create([
                                'order_receipt_id' => $orderReceipt->id,
                                'quote_id' => $quoteId
                            ]);
                        }
                    }

                    $orderReceiptItems = [];
                    foreach ($mappedValidItems as $itemData) {
                        $itemData['order_receipt_id'] = $orderReceipt->id;
                        $itemData['status'] = 'Validé';
                        $orderReceiptItem = $this->allItemRepository->setModel(OrderReceiptItem::class)->create($itemData);
                        $orderReceiptItems[] = $orderReceiptItem;
                    }

                    $response['order_receipt'] = $orderReceipt;
                    $response['order_receipt_items'] = $orderReceiptItems;
                    if (count($quoteIds) > 1) {
                        $response['order_receipt_quotes'] = $quoteIds;
                    }

                    foreach ($ids as $deliveryNoteId) {
                        $this->deliveryNoteRepository->update($deliveryNoteId, [
                            'status' => 'Terminé',
                            'order_receipt_id' => $orderReceipt->id
                        ]);
                    }
                }
            }

            return response()->json($response, 201);
        } catch (\Exception $e) {
            $statusCode = is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }
}
