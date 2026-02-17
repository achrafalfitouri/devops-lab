<?php
namespace App\Http\Controllers;

use App\Exports\OrderNoteExport;
use App\Helpers\FilterHelper;
use App\Helpers\NumberHelper;
use App\Models\OrderNote;
use App\Models\OrderNoteItem;
use App\Models\ProductionNoteItem;
use App\Models\Quote;
use App\Repositories\Contracts\DocumentItemRepositoryInterface;
use App\Repositories\Contracts\OrderNoteRepositoryInterface;
use App\Repositories\Contracts\ProductionNoteRepositoryInterface;
use App\Traits\HandlesConditionalRelationships;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class OrderNoteController extends Controller
{

    use HandlesConditionalRelationships;

    protected $orderNoteRepository;
    protected $allItemRepository;
    protected $ProductionNoteRepository;
    protected $ItemRepository;

    public function __construct(
        OrderNoteRepositoryInterface $orderNoteRepository,
        ProductionNoteRepositoryInterface $ProductionNoteRepository,
        DocumentItemRepositoryInterface $allItemRepository
    ) {
        $this->orderNoteRepository = $orderNoteRepository;
        $this->allItemRepository = $allItemRepository;
        $this->ProductionNoteRepository = $ProductionNoteRepository;
    }


    public function getOrderNotes(Request $request, OrderNoteRepositoryInterface $repository)
    {
        try {
            $filters = [
                'user_id' => $request->input('user'),
                'client_id' => $request->input('client'),
                'created_at' => $request->input('date'),
                'search' => $request->input('search'),
                'archive' => $request->input('archive'),
                'status' => $request->input('status'),
            ];

            $perPage = $request->input('per_page', 10);
            if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
                $query = $repository->getOrderNotes()->with([
                    'client:id,legal_name',
                    'user:id,full_name',
                    'items'
                ])->onlyTrashed();
            } else {
                $query = $repository->getOrderNotes()->with(['client:id,legal_name', 'user:id,full_name', 'items']);
            }


            $filterableFields = ['user_id', 'client_id', 'status'];
            $searchableFields = [
                'code',
                'total_phrase'
            ];

            $query = FilterHelper::applyFilters($query, $filters, $filterableFields);

            if (!empty($filters['created_at'])) {
                $query->whereDate('created_at', $filters['created_at']);
            }
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            $query = FilterHelper::applySearch($query, $filters['search'], $searchableFields);

            $query->orderBy('created_at', 'desc');
            $orderNotes = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'current_page' => $orderNotes->currentPage(),
                'total_order_notes' => $orderNotes->total(),
                'per_page' => $orderNotes->perPage(),
                'order_notes' => $orderNotes
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


    public function getOrderNoteById(Request $request, $id)
    {
        try {
            $isArchived = filter_var($request->query('archive'), FILTER_VALIDATE_BOOLEAN);

            $baseRelationships = [
                'client:id,legal_name,ice',
                'user:id,code,full_name',
                'items' => function ($query) {
                    $query->select('*')->orderBy('order');
                },
                // 'refunds' => function ($query) {
                //     $query->select('id', 'status', 'code', 'tax_amount', 'quote_id', 'is_taxable');
                // },
                'productionNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
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
                'quoterequests' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
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
                'orderNotes' => function ($query) {
                    $query->select('id','client_id', 'status', 'amount', 'code', 'tax_amount', 'quote_id', 'is_taxable','final_amount')
                    ->with([
                            'client:id,legal_name,ice,balance',
                        ])
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
                $orderNote = $this->orderNoteRepository->getOrderNotes()->onlyTrashed()->with($relationships)->find($id);
            } else {
                $orderNote = $this->orderNoteRepository->getOrderNotes()->with($relationships)->find($id);
            }

            if (!$orderNote) {
                return response()->json(['message' => 'Note de commande introuvable'], 404);
            }

            $orderNote = $this->mergeConditionalRelationships($orderNote);

            return response()->json($orderNote);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string',
            ]);

            $quote = $this->orderNoteRepository->findById($id);

            if (!$quote) {
                return response()->json(['message' => 'Devis introuvable.'], 404);
            }
            if ($quote->status === 'Terminé') {
                return response()->json(['message' => 'Ce devis ne peut pas être modifié'], 403);
            }

            $updated = $this->orderNoteRepository->update($id, ['status' => $request->input('status')]);

            if (!$updated) {
                return response()->json(['message' => 'Failed to update status'], 500);
            }

            $updatedQuote = $this->orderNoteRepository->findById($id);

            $responseQuote = [
                'id' => $updatedQuote->id,
                'status' => $updatedQuote->status,
            ];

            return response()->json([
                'message' => 'Statut mis à jour avec succès.',
                'quote' => $responseQuote,
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
            $validator = Validator::make($request->all(), [
                'quote_id'    => 'required|uuid|exists:quotes,id',
                'title'       => 'required|string',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message'    => 'La validation a échoué.',
                    'error' => $validator->errors()
                ], 422);
            }

            $data = $request->all();

            $quote = Quote::with('items')->find($data['quote_id']);
            if (!$quote) {
                throw new ModelNotFoundException('Devis introuvable.');
            }

            if ($quote->status !== 'Validé') {
                return response()->json([
                    'message' => 'Impossible de créer un nouveau document. Le statut actuel du document n est pas "Validé".'
                ], 400);
            }

            DB::beginTransaction();

            $orderNoteData = [
                'client_id'   => $quote->client_id,
                'quote_id'    => $quote->id,
                'status'      => 'Brouillon',
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
                'user_id'     => Auth::id(),
            ];
            $data = array_map(function ($value) {
                // Only convert truly empty values to null
                if ($value === '' || (empty($value) && $value !== 0 && $value !== '0' && $value !== false)) {
                    return null;
                }
                return $value;
            }, $data);

            $orderNote = $this->orderNoteRepository->create($orderNoteData);
            if (!isset($data['code'])) {
                $duplicateCodeExists = OrderNote::where('code', $orderNote->code)
                    ->where('id', '!=', $orderNote->id)
                    ->exists();

                if ($duplicateCodeExists) {
                    $this->orderNoteRepository->delete($orderNote->id);
                    return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
                }
            }
            $orderNoteItems = [];
            foreach ($quote->items as $item) {
                $itemData = [
                    'description'          => $item->description,
                    'price'                => $item->price,
                    'quantity'             => $item->quantity,
                    'undiscounted_amount'  => $item->undiscounted_amount,
                    'discount'             => $item->discount,
                    'amount'               => $item->amount,
                    'order'                => $item->order,
                    'status'               => 'Actif',
                    'order_note_id'        => $orderNote->id,
                ];
                $orderNoteItems[] =
                    $this->allItemRepository
                    ->setModel(OrderNoteItem::class)->create($itemData);
            }

            DB::commit();

            return response()->json([
                'order_note' => $orderNote,
                'items'      => $orderNoteItems
            ], 201);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message'   => 'Devis introuvable.',
                'error' => $e->getMessage()
            ], 404);
        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json([
                'message'   => 'Erreur de base de données.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message'   => 'Échec de la création de la note de commande.',
                'error' => $e->getMessage()
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
            $noteAmount = 0;

            $orderNote = $this->orderNoteRepository->findById($id);

            if (!$orderNote) {
                return response()->json(['message' => 'Note de commande introuvable.'], 404);
            }
            if ($orderNote->status === 'Validé' || $orderNote->status === 'Annulé' || $orderNote->status === 'Retourné' || $orderNote->status === 'Terminé') {
                return response()->json(['message' => ' ne peut pas être modifié'], 403);
            }
            if (isset($data['code']) && $data['code'] === $orderNote->code) {
                unset($data['code']);
            } else if (isset($data['code'])) {
                $validator = Validator::make($data, [
                    'code' => 'unique:order_notes,code,' . $id,
                ]);

                if ($validator->fails()) {
                    return response()->json(['message' => 'Ce code est déjà utilisé par un autre document.'], 422);
                }
            }

            $existingItems = $orderNote->items;

            $requestItemIds = isset($data['items']) ? array_column($data['items'], 'id') : [];

            $items = [];

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (isset($itemData['delete']) && $itemData['delete'] === true) {
                        if (isset($itemData['id'])) {
                            $this->allItemRepository
                                ->setModel(OrderNoteItem::class)->delete($itemData['id']);
                        }
                        continue;
                    }
                    $itemData['discount'] = isset($itemData['discount']) && is_numeric($itemData['discount'])
                        ? floatval($itemData['discount'])
                        : 0;

                    $itemData['undiscounted_amount'] = $itemData['price'] * $itemData['quantity'];
                    $itemData['amount'] = $itemData['undiscounted_amount'] - $itemData['discount'];

                    $noteAmount += $itemData['amount'];

                    if (isset($itemData['id']) && $itemData['id'] !== null && $itemData['id'] !== '') {
                        $this->allItemRepository
                            ->setModel(OrderNoteItem::class)->update($itemData['id'], $itemData);
                    } else {
                        $itemData['order_note_id'] = $id;
                        $this->allItemRepository
                            ->setModel(OrderNoteItem::class)->create($itemData);
                    }

                    $items[] = $itemData;
                }
            }

            $itemsToDelete = array_diff(array_column($existingItems->toArray(), 'id'), $requestItemIds);


            foreach ($itemsToDelete as $itemId) {
                $this->allItemRepository
                    ->setModel(OrderNoteItem::class)->delete($itemId);
            }

            $data['amount'] = $noteAmount;
            $data['tax_amount'] = $data['is_taxable'] ? $noteAmount * 0.2 : 0;
            $data['final_amount'] = $noteAmount + $data['tax_amount'];
            $data['total_phrase'] = NumberHelper::convertNumberToWords($data['final_amount']);
            $data = array_map(function ($value) {
                return empty($value) && $value !== 0 ? null : $value;
            }, $data);
            $orderNote = $this->orderNoteRepository->update($id, $data);

            return response()->json(['order_note' => $orderNote, 'items' => $items]);
        } catch (\Exception $e) {
            return response()->json(['message' => "Une erreur s'est produite lors de la mise à jour de la note de commande.", 'error' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $orderNote = $this->orderNoteRepository->findById($id);

            if (!$orderNote) {
                return response()->json(['message' => 'Note de commande introuvable.'], 404);
            }

            foreach ($orderNote->items as $item) {
                $this->allItemRepository
                    ->setModel(OrderNoteItem::class)->delete($item->id);
            }

            $deleted = $this->orderNoteRepository->delete($id);

            if ($deleted) {
                return response()->json(['message' => 'Note de commande et articles associés supprimés avec succès.']);
            }

            return response()->json(['message' => 'Échec de la suppression de la note de commande.'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => "Une erreur s'est produite lors de la suppression de la note de commande.", 'erro' => $e->getMessage()], 500);
        }
    }


    public function exportOrderNotes(Request $request)
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

            $fileName = 'order_notes_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
            $filePath = storage_path('app/public/' . $fileName);

            $export = new OrderNoteExport($filters, $perPage);

            Excel::store($export, 'public/' . $fileName);

            $downloadUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'Notes de commande exportées avec succès.',
                'download_url' => $downloadUrl,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Échec de l\'exportation des notes de commande : ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateDocument($id)
{
    try {
        $orderNote = $this->orderNoteRepository->getOrderNotes()->with(['client', 'user', 'items'])->find($id);

        if (!$orderNote) {
            return response()->json(['message' => 'Note de commande introuvable.'], 404);
        }
        if ($orderNote->status === "Terminé") {
            return response()->json(['message' => 'Note de commande Terminé '], 404);
        }

        if ($orderNote->status !== 'Validé') {
            return response()->json(['message' => 'La note de commande n\'est pas "Validée" et ne peut pas être traitée.'], 400);
        }

        if ($orderNote->items->isEmpty()) {
            return response()->json(['message' => 'Impossible de créer une note de production sans articles.'], 400);
        }

        $authUser = Auth::user();

        // Sort items by order instead of grouping
        $sortedItems = $orderNote->items->sortBy('order');

        $productionNotes = [];
        $lastProductionNote = null;
        $lastItemData = null;

        // Create one production note per item
        foreach ($sortedItems as $item) {
            $productionNoteData = [
                'quote_id' => $orderNote->quote_id,
                'client_id' => $orderNote->client_id,
                'is_taxable' => $orderNote->is_taxable,
                'status' => 'Brouillon',
                'production_comment' => $orderNote->order_comment,
                'user_id' => $orderNote->user_id,
                'order_note_id' => $orderNote->id,
            ];
            $productionNote = $this->ProductionNoteRepository->create($productionNoteData);

            $itemData = [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'discount' => $item->discount,
                'undiscounted_amount' => $item->undiscounted_amount,
                'amount' => $item->amount,
                'production_note_id' => $productionNote->id,
                'order' => $item->order,
            ];

            $this->allItemRepository
                ->setModel(ProductionNoteItem::class)->create($itemData);

            $productionNotes[] = [
                'production_note' => $productionNote,
                'items' => [$itemData],
            ];

            $lastProductionNote = $productionNote;
            $lastItemData = [$itemData];
        }

        $this->orderNoteRepository->update($id, ["status" => "Terminé"]);

        return response()->json(['production_note' => $lastProductionNote, 'items' => $lastItemData], 201);
    } catch (\Exception $e) {
        $statusCode = $e->getCode();

        if (!is_int($statusCode) || $statusCode < 100 || $statusCode >= 600) {
            $statusCode = 500;
        }

        return response()->json([
            'status' => $statusCode,
            'message' => $e->getMessage(),
        ], $statusCode);
    }
}

    public function generatePdf($id)
    {
        try {
            $OrderNote = OrderNote::with(['client', 'user', 'items'])->find($id);

            if (!$OrderNote) {
                return response()->json(['message' => 'Reçu de commande introuvable.'], 404);
            }

            $Data = [
                'customer_name' => $OrderNote->client->legal_name ?? 'N/A',
                'customer_address' => $OrderNote->client->address ?? 'N/A',
                'total_in_words' => $OrderNote->total_phrase ?? 'N/A',
                'comment' => $OrderNote->note ?? 'N/A',
                'total_ht' => $OrderNote->amount,
                'discount' => $OrderNote->discount ?? 0,
                'tva' => $OrderNote->tax_amount,
                'total_ttc' => $OrderNote->final_amount,
                'validity_date' => $OrderNote->due_date ?? 'N/A',
                'items' => $OrderNote->items->map(function ($item) {
                    return [
                        'designation' => $item->description ?? 'N/A',
                        'price' => $item->price ?? 0,
                        'quantity' => $item->quantity ?? 0,
                        'total' => $item->amount ?? 0,
                    ];
                })->toArray(),
            ];

            $pdf = Pdf::loadView('pdf.order_note', ['orderNote' => $Data]);

            $fileName = 'order_note' . $OrderNote->code . '_' . time() . '.pdf';


            $pdf->save(storage_path('app/public/' . $fileName));

            $publicUrl = asset('storage/' . $fileName);
            return response()->json([
                'status' => 200,
                'message' => 'PDF généré avec succès.',
                'download_url' => $publicUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Une erreur s'est produite lors de la génération du PDF.",

                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
