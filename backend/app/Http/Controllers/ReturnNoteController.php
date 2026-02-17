<?php

namespace App\Http\Controllers;

use App\Exports\ReturnNoteExport;
use App\Helpers\FilterHelper;
use App\Helpers\NumberHelper;
use App\Models\DeliveryNote;
use App\Models\RefundNote;
use App\Models\ReturnNoteItem;
use App\Repositories\Contracts\ReturnNoteRepositoryInterface;
use App\Repositories\Contracts\DocumentItemRepositoryInterface;
use App\Traits\HandlesConditionalRelationships;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ReturnNoteController extends Controller
{
    use HandlesConditionalRelationships;
    protected $returnNoteRepository;
    protected $documentItemRepository;

    public function __construct(ReturnNoteRepositoryInterface $returnNoteRepository, DocumentItemRepositoryInterface $documentItemRepository)
    {
        $this->returnNoteRepository = $returnNoteRepository;
        $this->documentItemRepository = $documentItemRepository;
    }

    public function getReturnNotes(Request $request, ReturnNoteRepositoryInterface $repository)
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

            $perPage = $request->input('per_page', 10);
            if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
                $query = $repository->getReturnNotes()->with([
                    'client:id,legal_name',
                    'user:id,full_name',
                    'items'
                ])->onlyTrashed();
            } else {
                $query = $repository->getReturnNotes()->with(['client:id,legal_name', 'user:id,full_name', 'items',]);
            }

            $filterableFields = ['user_id', 'client_id', 'created_at', 'quote_id', 'status'];
            $searchableFields = ['code', 'total_phrase'];

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
            $returnNotes = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'current_page' => $returnNotes->currentPage(),
                'total_return_notes' => $returnNotes->total(),
                'per_page' => $returnNotes->perPage(),
                'return_notes' => $returnNotes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }


    public function getReturnNoteById(Request $request, $id)
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
            'productionNotes' => function ($query) {
                $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
            },
            // 'returnNotes' => function ($query) {
            //     $query->select('id','client_id',  'status', 'amount', 'code', 'tax_amount', 'quote_id', 'is_taxable' , 'final_amount')
            //     ->with([
            //         'client:id,legal_name,ice,balance',
            //     ])
            //     ->orderBy('code');
            // },
            // 'refunds' => function ($query) {
            //     $query->select('id', 'status', 'code', 'tax_amount', 'quote_id', 'is_taxable');
            // },
        ];

        $conditionalRelationships = [
            'invoices' => function ($query) {
                $query->select('id', 'status', 'code', 'quote_id', 'is_taxable', 'process_group_id');
            },
            'orderReceipts' => function ($query) {
                $query->select('id', 'status', 'code', 'quote_id', 'is_taxable', 'process_group_id');
            },
            'returnNotes' => function ($query) {
                $query->select('id','client_id',  'status', 'amount', 'code', 'tax_amount', 'quote_id', 'is_taxable' , 'final_amount','process_group_id')
                ->with([
                    'client:id,legal_name,ice,balance',
                ])
                ->orderBy('code');
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
            $returnNote = $this->returnNoteRepository->getReturnNotes()->onlyTrashed()->with($relationships)->find($id);
        } else {
            $returnNote = $this->returnNoteRepository->getReturnNotes()->with($relationships)->find($id);
        }

        if (!$returnNote) {
            return response()->json(['message' => 'Return Note introuvable'], 404);
        }
        $returnNote = $this->mergeConditionalRelationships($returnNote);

        return response()->json($returnNote);
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

            $updatedQuotes = [];
            $errors = [];

            foreach ($ids as $id) {
                $quote = $this->returnNoteRepository->findById($id);

                if (!$quote) {
                    $errors[] = ['id' => $id, 'error' => 'Note de retour introuvable'];
                    continue;
                }

                $updated = $this->returnNoteRepository->update($id, ['status' => $status]);

                if (!$updated) {
                    $errors[] = ['id' => $id, 'error' => 'Échec de la mise à jour du statut'];
                    continue;
                }

                $updatedQuote = $this->returnNoteRepository->findById($id);
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

    public function create(Request $request)
    {
        try {
            $data = $request->all();

            $DeliveryNote = DeliveryNote::with('items')->find($data['delivery_note_id']);
            if (!$DeliveryNote) {
                return response()->json(['message' => 'Bon de livraison introuvable.'], 404);
            }

            if ($DeliveryNote->status !== 'Validé') {
                return response()->json(['message' => 'Impossible de créer un nouveau document. Le statut du document actuel n\'est pas "Validé".'], 400);
            }

            $data['amount'] = $DeliveryNote->amount;
            $data['tax_amount'] = $DeliveryNote->tax_amount;
            $data['final_amount'] = $DeliveryNote->final_amount;
            $data['total_phrase'] = $DeliveryNote->total_phrase;
            $data['client_id'] = $DeliveryNote->client_id;
            $data['is_taxable'] = $DeliveryNote->is_taxable;
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

            $returnNote = $this->returnNoteRepository->create($data);
            if (!isset($data['code'])) {
                $duplicateCodeExists = RefundNote::where('code', $returnNote->code)
                    ->where('id', '!=', $returnNote->id)
                    ->exists();

                if ($duplicateCodeExists) {
                    $this->delete($returnNote->id);
                    return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
                }
            }

            $items = $DeliveryNote->items->map(function ($item) use ($DeliveryNote) {
                return [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'discount' => $item->discount,
                    'undiscounted_amount' => $item->undiscounted_amount,
                    'amount' => $item->amount,
                    'delivery_note_id' => $DeliveryNote->id,
                ];
            });

            foreach ($items as $itemData) {
                $this->documentItemRepository->create($itemData);
            }

            return response()->json(['return_note' => $returnNote, 'items' => $items], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Échec de la création de la note de commande.', 'error' => $e->getMessage()], 500);
        }
    }



    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();
            if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
                return response()->json(['message' => 'Pas d\'articles fournis. La note de retour doit contenir au moins un article.'], 422);
            }
            $returnNoteAmount = 0;

            $returnNote = $this->returnNoteRepository->findById($id);

            if (!$returnNote) {
                return response()->json(['message' => 'Note de retour introuvable.'], 404);
            }
            if (isset($data['code']) && $data['code'] === $returnNote->code) {
                unset($data['code']);
            } else if (isset($data['code'])) {
                $validator = Validator::make($data, [
                    'code' => 'unique:return_notes,code,' . $id,
                ]);

                if ($validator->fails()) {
                    return response()->json(['message' => 'Ce code est déjà utilisé par un autre document.'], 422);
                }
            }
            $existingItems = $returnNote->items;

            $requestItemIds = isset($data['items']) ? array_column($data['items'], 'id') : [];

            $items = [];

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (isset($itemData['delete']) && $itemData['delete'] === true) {
                        if (isset($itemData['id'])) {
                            $this->documentItemRepository->delete($itemData['id']);
                        }
                        continue;
                    }

                    $itemData['discount'] = isset($itemData['discount']) && is_numeric($itemData['discount'])
                        ? floatval($itemData['discount'])
                        : 0;

                    $itemData['undiscounted_amount'] = $itemData['price'] * $itemData['quantity'];
                    $itemData['amount'] = $itemData['undiscounted_amount'] - $itemData['discount'];

                    $returnNoteAmount += $itemData['amount'];
                    if (isset($itemData['id']) && $itemData['id'] !== null && $itemData['id'] !== '') {
                        $this->documentItemRepository
                            ->setModel(ReturnNoteItem::class)->update($itemData['id'], $itemData);
                    } else {
                        $itemData['return_note_id'] = $id;
                        $this->documentItemRepository
                            ->setModel(ReturnNoteItem::class)->create($itemData);
                    }
                    $items[] = $itemData;
                }
            }

            $itemsToDelete = array_diff(array_column($existingItems->toArray(), 'id'), $requestItemIds);

            foreach ($itemsToDelete as $itemId) {
                $this->documentItemRepository->delete($itemId);
            }


            $data['amount'] = $returnNoteAmount;
            $data['tax_amount'] = $data['is_taxable'] ? $returnNoteAmount * 0.2 : 0;
            $data['final_amount'] = $returnNoteAmount + $data['tax_amount'];
            $data['total_phrase'] = NumberHelper::convertNumberToWords($data['final_amount']);
            $data = array_map(function ($value) {
                // Only convert truly empty values to null
                if ($value === '' || (empty($value) && $value !== 0 && $value !== '0' && $value !== false)) {
                    return null;
                }
                return $value;
            }, $data);

            $returnNote = $this->returnNoteRepository->update($id, $data);

            return response()->json(['returnnote' => $returnNote, 'items' => $items]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Une erreur est survenue lors de la mise à jour de la note de retour.', 'error' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $returnNote = $this->returnNoteRepository->findById($id);

            if (!$returnNote) {
                return response()->json(['message' => 'Note de retour introuvable.'], 404);
            }
            foreach ($returnNote->items as $item) {
                $this->documentItemRepository->delete($item->id);
            }

            $deleted = $this->returnNoteRepository->delete($id);

            if ($deleted) {
                return response()->json(['message' => 'Note de retour et éléments associés supprimés avec succès.']);
            }

            return response()->json(['message' => 'Échec de la suppression de la note de retour.'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Une erreur est survenue lors de la suppression de la note de retour', 'error' => $e->getMessage()], 500);
        }
    }


    public function export(Request $request)
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

            $fileName = 'returne_notes_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

            $export = new ReturnNoteExport($filters, $perPage);

            Excel::store($export, 'public/' . $fileName);

            $downloadUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'Note de retour exportée avec succès.',
                'download_url' => $downloadUrl,
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Échec de l\'exportation des bons de livraison.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function generatePdf($id)
    {
        try {
            $returnNote = $this->returnNoteRepository
                ->getReturnNotes()
                ->with(['client', 'user', 'items'])
                ->find($id);

            if (!$returnNote) {
                return response()->json(['message' => 'Note de retour introuvable.'], 404);
            }

            $Data = [
                'customer_name' => $returnNote->client->legal_name ?? 'N/A',
                'customer_address' => $returnNote->client->address ?? 'N/A',
                'total_in_words' => $returnNote->total_phrase,
                'comment' => $returnNote->delivery_comment,
                'total_ht' => $returnNote->amount,
                'discount' => $returnNote->discount ?? 0,
                'tva' => $returnNote->tax_amount,
                'total_ttc' => $returnNote->final_amount,
                'validity_date' => $returnNote->validity_date ?? 'N/A',
                'items' => $returnNote->items->map(function ($item) {
                    return [
                        'designation' => $item->description,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'total' => $item->amount,
                    ];
                })->toArray(),
            ];

            $pdf = Pdf::loadView('pdf.return_note', ['returnNote' => $Data]);

            $fileName = 'return_note_' . $returnNote->id . '_' . time() . '.pdf';

            $pdf->save(storage_path('app/public/' . $fileName));

            $publicUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'PDF généré avec succès.',
                'download_url' => $publicUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la génération du PDF.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
