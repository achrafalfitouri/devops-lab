<?php

namespace App\Http\Controllers;

use App\Exports\OutputNoteExport;
use App\Helpers\FilterHelper;
use App\Helpers\NumberHelper;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\Invoice;
use App\Models\InvoiceCredit;
use App\Models\OutputNote;
use App\Models\OutputNoteItem;
use App\Models\ProductionNote;
use App\Repositories\Contracts\DeliveryNoteRepositoryInterface;
use App\Repositories\Contracts\OutputNoteRepositoryInterface;
use App\Repositories\Contracts\DocumentItemRepositoryInterface;
use App\Traits\HandlesConditionalRelationships;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class OutputNoteController extends Controller
{       use HandlesConditionalRelationships;

    protected $outputNoteRepository;
    protected $allItemRepository;
    protected $deliveryNoteRepository;


    public function __construct(OutputNoteRepositoryInterface $outputNoteRepository, DocumentItemRepositoryInterface $allItemRepository, DeliveryNoteRepositoryInterface $deliveryNoteRepository,)
    {
        $this->outputNoteRepository = $outputNoteRepository;
        $this->allItemRepository = $allItemRepository;
        $this->deliveryNoteRepository = $deliveryNoteRepository;
    }

    public function getOutputNotes(Request $request, OutputNoteRepositoryInterface $repository)
    {
        try {
            $filters = [
                'user_id' => $request->input('user'),
                'client_id' => $request->input('client'),
                'created_at' => $request->input('date'),
                'search' => $request->input('search'),
                'quote_id' => $request->input('quote'),
                'archive' => $request->input('archive'),
                'status' => $request->input('status'),

            ];

            // Normalize incoming date (DD/MM/YYYY -> YYYY-MM-DD)
            if (!empty($filters['created_at'])) {
                try {
                    if (strpos($filters['created_at'], '/') !== false) {
                        $filters['created_at'] = Carbon::createFromFormat('d/m/Y', trim($filters['created_at']))->toDateString();
                    } else {
                        $filters['created_at'] = Carbon::parse(trim($filters['created_at']))->toDateString();
                    }
                } catch (\Exception $e) {
                    $filters['created_at'] = null;
                }
            }

            $perPage = $request->input('per_page', 10);
            if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
                $query = $repository->getQuery()->with([
                    'client:id,legal_name',
                    'user:id,full_name',
                    'items'
                ])->onlyTrashed();
            } else {
                $query = $repository->getQuery()->with(['client:id,legal_name', 'user:id,full_name', 'items']);
            }

            $filterableFields = ['user_id', 'client_id', 'quote_id', 'status'];
            $searchableFields = ['code', 'total_phrase'];

            $query = FilterHelper::applyFilters($query, $filters, $filterableFields);

            if (!empty($filters['created_at'])) {
                $query->whereDate('created_at', $filters['created_at']);
            }
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            $query = FilterHelper::applySearch($query, $filters['search'], $searchableFields);

            $query->orderBy('created_at', 'desc');
            $outputNotes = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'current_page' => $outputNotes->currentPage(),
                'total_output_notes' => $outputNotes->total(),
                'per_page' => $outputNotes->perPage(),
                'output_notes' => $outputNotes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], is_int($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }


    public function getOutputNoteById(Request $request, $id)
    {
        try {
            $isArchived = filter_var($request->query('archive'), FILTER_VALIDATE_BOOLEAN);

            $baseRelationships = [
                'client:id,legal_name,ice',
                'user:id,code,full_name',
                'items' => function ($query) {
                    $query->select("*")->orderBy('order');
                },
                'orderNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                },
                // 'refunds' => function ($query) {
                //     $query->select('id', 'status', 'code', 'tax_amount', 'quote_id', 'is_taxable');
                // },
                'productionNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                },
                'deliveryNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                },
                'quotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'is_taxable');
                },
                'quoterequests' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                },
                // 'orderReceipts' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                // },
                // 'invoiceCredits' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                // },
                // 'returnNotes' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                // },
                'outputNotes' => function ($query) {
                    $query->select('id', 'client_id', 'status', 'code', 'amount', 'tax_amount', 'quote_id', 'is_taxable', 'final_amount')
                        ->with(['client:id,legal_name,ice,balance'])
                        ->orderBy('code');
                },
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
                $outputNote = $this->outputNoteRepository->getOutputNotes()->onlyTrashed()->with($relationships)->find($id);
            } else {
                $outputNote = $this->outputNoteRepository->getOutputNotes()->with($relationships)->find($id);
            }

            if (!$outputNote) {
                return response()->json(['message' => 'Note de sortie introuvable'], 404);
            }

            $outputNote = $this->mergeConditionalRelationships($outputNote);


            return response()->json($outputNote);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
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

            $updatedNotes = [];
            $errors = [];

            foreach ($ids as $id) {
                $note = $this->outputNoteRepository->findById($id);

                if (!$note) {
                    $errors[] = ['id' => $id, 'error' => 'Note de sortie introuvable'];
                    continue;
                }

                if ($note->status === 'Terminé') {
                    $errors[] = ['id' => $id, 'error' => 'Cette note est déjà terminée et ne peut pas être modifiée'];
                    continue;
                }

                $updated = $this->outputNoteRepository->update($id, ['status' => $status]);

                if (!$updated) {
                    $errors[] = ['id' => $id, 'error' => 'Échec de la mise à jour du statut'];
                    continue;
                }

                $updatedNote = $this->outputNoteRepository->findById($id);
                $updatedNotes[] = [
                    'id' => $updatedNote->id,
                    'status' => $updatedNote->status,
                ];
            }

            return response()->json([
                'message' => 'Mise à jour des statuts terminée.',
                'updated' => $updatedNotes,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Une erreur s'est produite lors de la mise à jour du statut.",
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create(Request $request)
    {
        try {
            $data = $request->all();

            $ProductionNote = ProductionNote::with('items')->find($data['production_note_id']);
            if (!$ProductionNote) {
                return response()->json(['message' => 'Devis introuvable.'], 404);
            }
            if ($ProductionNote->status !== 'Validé') {
                return response()->json(['message' => 'Impossible de créer un nouveau document. Le statut du document actuel n est pas "Validé".'], 400);
            }

            $data['amount'] = $ProductionNote->amount;
            $data['tax_amount'] = $ProductionNote->tax_amount;
            $data['final_amount'] = $ProductionNote->final_amount;
            $data['total_phrase'] = $ProductionNote->total_phrase;
            $data['client_id'] = $ProductionNote->client_id;
            $data['is_taxable'] = $ProductionNote->is_taxable;
            $data['status'] = 'Brouillon';



            $authUser = Auth::user();
            $data['user_id'] = $authUser ? $authUser->id : null;
            $data = array_map(function ($value) {
                // Only convert truly empty values to null
                if ($value === '' || (empty($value) && $value !== 0 && $value !== '0' && $value !== false)) {
                    return null;
                }
                return $value;
            }, $data);

            $outputNote = $this->outputNoteRepository->create($data);
            if (!isset($data['code'])) {
                $duplicateCodeExists = OutputNote::where('code', $outputNote->code)
                    ->where('id', '!=', $outputNote->id)
                    ->exists();

                if ($duplicateCodeExists) {
                    $this->outputNoteRepository->delete($outputNote->id);
                    return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
                }
            }


            $items = $ProductionNote->items->map(function ($item) use ($outputNote) {
                return [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'discount' => $item->discount,
                    'undiscounted_amount' => $item->undiscounted_amount,
                    'amount' => $item->amount,
                    'order_note_id' => $outputNote->id,
                ];
            });

            foreach ($items as $itemData) {
                $this->allItemRepository
                    ->setModel(OutputNoteItem::class)->create($itemData);
            }

            return response()->json(['output_note' => $outputNote, 'items' => $items], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Échec de la création de la note de sortie.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();
            if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
                return response()->json(['message' => 'Pas d\'articles fournis. La note de retour doit contenir au moins un article.'], 422);
            }
            $noteAmount = 0;

            $outputNote = $this->outputNoteRepository->findById($id);

            if (!$outputNote) {
                return response()->json(['message' => 'Output Note not found'], 404);
            }
            $validator = Validator::make($data, [
                'code' => 'unique:output_notes,code,' . $id,
            ]);
            if ($outputNote->status === 'Validé' || $outputNote->status === 'Annulé' || $outputNote->status === 'Retourné' || $outputNote->status === 'Terminé') {
                return response()->json(['message' => 'Ce outputNote ne peut pas être modifié'], 403);
            }
            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()], 422);
            }
            $existingItems = $outputNote->items;

            $requestItemIds = isset($data['items']) ? array_column($data['items'], 'id') : [];

            $items = [];

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (isset($itemData['delete']) && $itemData['delete'] === true) {
                        if (isset($itemData['id'])) {
                            $this->allItemRepository
                                ->setModel(OutputNoteItem::class)->delete($itemData['id']);
                        }
                        continue;
                    }

                    $itemData['discount'] = isset($itemData['discount']) && is_numeric($itemData['discount'])
                        ? floatval($itemData['discount'])
                        : 0;

                    $itemData['undiscounted_amount'] = $itemData['price'] * $itemData['quantity'];
                    $itemData['amount'] = $itemData['undiscounted_amount'] - $itemData['discount'];

                    $noteAmount += $itemData['amount'];

                    // CHANGED: Only sanitize nullable foreign key fields, not numeric fields
                    // Convert empty strings to null for foreign keys and optional text fields
                    if (isset($itemData['production_note_id']) && $itemData['production_note_id'] === '') {
                        $itemData['production_note_id'] = null;
                    }
                    if (isset($itemData['status']) && $itemData['status'] === '') {
                        $itemData['status'] = null;
                    }

                    if (isset($itemData['id']) && $itemData['id'] !== null && $itemData['id'] !== '') {
                        $this->allItemRepository
                            ->setModel(OutputNoteItem::class)->update($itemData['id'], $itemData);
                    } else {
                        $itemData['output_note_id'] = $id;
                        $this->allItemRepository
                            ->setModel(OutputNoteItem::class)->create($itemData);
                    }

                    $items[] = $itemData;
                }
            }

            $itemsToDelete = array_diff(array_column($existingItems->toArray(), 'id'), $requestItemIds);

            foreach ($itemsToDelete as $itemId) {
                $this->allItemRepository
                    ->setModel(OutputNoteItem::class)->delete($itemId);
            }

            // Remove items from data before sanitization
            unset($data['items']);

            $data['amount'] = $noteAmount;
            $data['tax_amount'] = isset($data['is_taxable']) && $data['is_taxable'] ? $noteAmount * 0.2 : 0;
            $data['final_amount'] = $noteAmount + $data['tax_amount'];
            $data['total_phrase'] = NumberHelper::convertNumberToWords($data['final_amount']);

            // CHANGED: Only convert empty strings to null, preserve all numeric values including 0
            $data = array_map(function ($value) {
                // Only convert empty strings to null, preserve everything else (including 0, false, '0')
                return $value === '' ? null : $value;
            }, $data);

            $outputNote = $this->outputNoteRepository->update($id, $data);

            return response()->json(['outputnote' => $outputNote, 'items' => $items]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Une erreur est survenue lors de la mise à jour de la note de sortie', 'error' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $outputNote = $this->outputNoteRepository->findById($id);

            if (!$outputNote) {
                return response()->json(['message' => 'Note de sortie introuvable'], 404);
            }


            foreach ($outputNote->items as $item) {
                $this->allItemRepository
                    ->setModel(OutputNoteItem::class)->delete($item->id);
            }

            $deleted = $this->outputNoteRepository->delete($id);

            if ($deleted) {
                return response()->json(['message' => 'Note de sortie et éléments associés supprimés avec succès']);
            }

            return response()->json(['message' => 'Échec de la suppression de la note de sortie'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Une erreur est survenue lors de la suppression de la note de sortie.', 'error' => $e->getMessage()], 500);
        }
    }


    public function exportOutputNotes(Request $request)
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

            $fileName = 'output_notes_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
            $filePath = storage_path('app/public/' . $fileName);

            $export = new OutputNoteExport($filters, $perPage);

            Excel::store($export, 'public/' . $fileName);

            $downloadUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'Notes de sortie exportées avec succès.',
                'download_url' => $downloadUrl,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Échec de l\'exportation des notes de sortie : ' . $e->getMessage(),
            ], 500);
        }
    }
    public function generatePdf($id)
    {
        try {
            $outputNote = $this->outputNoteRepository->getOutputNotes()->with(['client', 'user', 'items'])->find($id);

            if (!$outputNote) {
                return response()->json(['message' => 'Output Note not found'], 404);
            }

            $outputNoteData = [
                'customer_name' => $outputNote->client->legal_name ?? 'N/A',
                'customer_address' => $outputNote->client->address ?? 'N/A',
                'total_in_words' => $outputNote->total_phrase,
                'comment' => $outputNote->delivery_comment,
                'total_ht' => $outputNote->amount,
                'discount' => $outputNote->discount ?? 0,
                'tva' => $outputNote->tax_amount,
                'total_ttc' => $outputNote->final_amount,
                'validity_date' => $outputNote->validity_date ?? 'N/A',
                'items' => $outputNote->items->map(function ($item) {
                    return [
                        'designation' => $item->description,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'total' => $item->amount,
                    ];
                })->toArray(),
            ];

            $pdf = Pdf::loadView('pdf.output_note', ['outputNote' => $outputNoteData]);

            $fileName = 'output_note_' . $outputNote->id . '_' . time() . '.pdf';


            $pdf->save(storage_path('app/public/' . $fileName));

            $publicUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'PDF généré avec succès',
                'download_url' => $publicUrl,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Une erreur est survenue lors de la génération du PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function generateDocument(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json(['message' => 'Aucune note de sortie sélectionnée.'], 400);
            }
            $allItems = [];
            $deliveryNote = null;
            $totalProductionAmount = 0;
            $isTaxable = false;
            $quoteId = null;
            $clientId = null;
            $outputComment = null;

            foreach ($ids as $index => $id) {
                $outputNote =
                    $this->outputNoteRepository
                    ->getOutputNotes()->with(['client', 'user', 'items'])
                    ->find($id);

                if (!$outputNote) {
                    return response()->json(['message' => 'Note de sortie introuvable.'], 404);
                }
                if ($outputNote->status === "Terminé") {
                    return response()->json(['message' => 'Note de sortie Terminé '], 404);
                }

                if ($outputNote->status !== 'Validé') {
                    return response()->json(['message' => 'La note de sortie n\'est pas validée et ne peut pas être traitée.'], 400);
                }

                if (!$outputNote->quote_id) {
                    return response()->json(['message' => 'La note de sortie n\'est pas liée à un devis valide.'], 400);
                }

                if ($deliveryNote === null) {
                    $isTaxable = $outputNote->is_taxable;
                    $quoteId = $outputNote->quote_id;
                    $clientId = $outputNote->client_id;
                    $outputComment = $outputNote->delivery_comment;
                }

                $outputNoteAmount = 0;
                $outputNoteItems = collect($outputNote->items)->map(function ($item) use (&$outputNoteAmount) {
                    $discount = max(0, $item->discount ?? 0);

                    $undiscountedAmount = $item->price * $item->quantity;
                    $itemAmount = $undiscountedAmount - $discount;

                    $outputNoteAmount += $itemAmount;

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
                });

                $totalProductionAmount += $outputNoteAmount;

                if ($index === 0) {
                    $taxAmount = $isTaxable ? $totalProductionAmount * 0.2 : 0;
                    $finalAmount = $totalProductionAmount + $taxAmount;

                    $data = [
                        'quote_id' => $quoteId,
                        'amount' => $totalProductionAmount,
                        'tax_amount' => $taxAmount,
                        'final_amount' => $finalAmount,
                        'total_phrase' => NumberHelper::convertNumberToWords($finalAmount),
                        'client_id' => $clientId,
                        'is_taxable' => $isTaxable,
                        'status' => 'Brouillon',
                        'delivery_comment' => $outputComment,
                        'output_note_id' => $outputNote->id
                    ];

                    $authUser = Auth::user();
                    $data['user_id'] = $authUser ? $authUser->id : null;

                    if ($outputNoteItems->isEmpty()) {
                        return response()->json(['message' => 'Impossible de créer une note de livraison sans articles.'], 400);
                    }

                    $deliveryNote = $this->deliveryNoteRepository->create($data);
                }

                foreach ($outputNoteItems as $itemData) {
                    $itemData['delivery_note_id'] = $deliveryNote->id;
                    $itemData['status'] = 'Validé';

                    $this->allItemRepository
                        ->setModel(DeliveryNoteItem::class)->create($itemData);
                    $allItems[] = $itemData;
                }

                $this->outputNoteRepository->update($id, ["status" => "Terminé"]);
            }

            return response()->json(['delivery_note' => $deliveryNote, 'items' => $allItems], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 500,
                'message' => $e->getMessage(),
            ], $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 500);
        }
    }
}
