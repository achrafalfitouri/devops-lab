<?php

namespace App\Http\Controllers;

use App\Exports\OrderReceiptExport;
use App\Helpers\FilterHelper;
use App\Helpers\NumberHelper;
use App\Models\OrderReceipt;
use App\Models\OrderReceiptItem;
use App\Models\RefundItem;
use App\Models\RefundNoteQuote;
use App\Repositories\Contracts\DocumentItemRepositoryInterface;
use App\Repositories\Contracts\RefundNoteRepositoryInterface;
use App\Repositories\Contracts\OrderReceiptRepositoryInterface;
use App\Traits\HandlesConditionalRelationships;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class OrderReceiptController extends Controller
{
    use HandlesConditionalRelationships;

    protected $orderReceiptRepository;
    protected $allItemRepository;
    protected $RefundNoteRepository;
    protected $invoiceCreditItemRepository;


    public function __construct(OrderReceiptRepositoryInterface $orderReceiptRepository, DocumentItemRepositoryInterface $allItemRepository, RefundNoteRepositoryInterface $RefundNoteRepository)
    {
        $this->orderReceiptRepository = $orderReceiptRepository;
        $this->allItemRepository = $allItemRepository;
        $this->RefundNoteRepository = $RefundNoteRepository;
    }

    public function getorderReciept(Request $request)
    {
        try {
            $filters = [
                'user_id' => $request->input('user'),
                'client_id' => $request->input('client'),
                'created_at' => $request->input('date'),
                'search' => $request->input('search'),
                'quote_id' => $request->input('quote'),
                'status' => $request->input('status'),
                'archive' => $request->input('archive'),
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
                $query = $this->orderReceiptRepository->getQuery()->with([
                    'client:id,legal_name',
                    'user:id,full_name',
                    'items'
                ])->onlyTrashed();
            } else {
                $query = $this->orderReceiptRepository->getQuery()->with(['client:id,legal_name,balance', 'user:id,full_name', 'items']);
            }

            $filterableFields = ['user_id', 'client_id', 'quote_id', 'status'];
            $searchableFields = ['code'];

            $query = FilterHelper::applyFilters($query, $filters, $filterableFields);

            if (!empty($filters['created_at'])) {
                $query->whereDate('created_at', $filters['created_at']);
            }
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $query = FilterHelper::applySearch($query, $filters['search'], $searchableFields);

            $query->orderBy('created_at', 'desc');
            $orderReceipt = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'current_page' => $orderReceipt->currentPage(),
                'total_orderReceipt' => $orderReceipt->total(),
                'per_page' => $orderReceipt->perPage(),
                'orderReceipt' => $orderReceipt
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }


    public function getOrderReceiptById(Request $request, $id)
    {
        try {
            $isArchived = filter_var($request->query('archive'), FILTER_VALIDATE_BOOLEAN);

            $baseRelationships = [
                'client:id,legal_name,ice,balance',
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
                'outputNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                },
                'deliveryNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                },
                'quotes' => function ($query) {
                    $query->select('id', 'status', 'code');
                },
                'quoterequests' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                },
                // 'invoices' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                // },
                'productionNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                },
                // 'invoiceCredits' => function ($query) {
                //     $query->select('id', 'status', 'code', 'tax_amount', 'quote_id', 'is_taxable', 'amount')
                //         ->with(['items' => function ($subQuery) {
                //             $subQuery->select('*')->orderBy('order');
                //         }])
                //         ->orderBy('code');
                // },
                // 'returnNotes' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                // },
                // 'orderReceipts' => function ($query) {
                //     $query->select('id','client_id',  'status', 'code', 'tax_amount', 'quote_id', 'is_taxable', 'amount', "payed_amount", 'final_amount')
                //         ->with([
                //             'client:id,legal_name,ice,balance',
                //             'items' => function ($subQuery) {
                //             $subQuery->select('*')->orderBy('order');
                //         }])
                //         ->orderBy('code');
                // }
            ];


            $conditionalRelationships = [
                'invoices' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable', 'process_group_id');
                },
                'orderReceipts' => function ($query) {
                    $query->select('id','client_id',  'status', 'code', 'tax_amount', 'quote_id', 'is_taxable', 'amount', "payed_amount", 'final_amount','process_group_id')
                        ->with([
                            'client:id,legal_name,ice,balance',
                            'items' => function ($subQuery) {
                            $subQuery->select('*')->orderBy('order');
                        }])
                        ->orderBy('code');
                },
                'returnNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable', 'process_group_id');
                },
                'invoiceCredits' => function ($query) {
                    $query->select('id', 'status', 'code', 'tax_amount', 'quote_id', 'is_taxable', 'amount','process_group_id')
                        ->with(['items' => function ($subQuery) {
                            $subQuery->select('*')->orderBy('order');
                        }])
                        ->orderBy('code');
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
                $orderReceipt = $this->orderReceiptRepository->all()->onlyTrashed()->with($relationships)->find($id);
            } else {
                $orderReceipt = $this->orderReceiptRepository->all()->with($relationships)->find($id);
            }

            if (!$orderReceipt) {
                return response()->json(['message' => 'Reçu de commande introuvable'], 404);
            }
            $orderReceipt = $this->mergeConditionalRelationships($orderReceipt);

            return response()->json($orderReceipt);
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
            $data = $request->all();

            if (!isset($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
                return response()->json(['message' => 'Aucun article fourni.'], 400);
            }

            $orderReceiptAmount = 0;
            $items = [];

            foreach ($data['items'] as $itemData) {
                if (!isset($itemData['price'], $itemData['quantity'], $itemData['discount'])) {
                    return response()->json(['message' => 'Champ prix, quantité ou remise manquant dans les articles.'], 400);
                }

                if ($itemData['discount'] < 0) {
                    return response()->json(['message' => 'La remise ne peut pas être inférieure à 0.'], 400);
                }

                $itemData['undiscounted_amount'] = $itemData['price'] * $itemData['quantity'];
                $itemData['amount'] = $itemData['undiscounted_amount'] - $itemData['discount'];

                $orderReceiptAmount += $itemData['amount'];

                $items[] = $itemData;
            }

            $data['amount'] = $orderReceiptAmount;

            $data['tax_amount'] = $data['is_taxable'] ? $orderReceiptAmount * 0.2 : 0;
            $data['final_amount'] = $orderReceiptAmount + $data['tax_amount'];
            $data['total_phrase'] = NumberHelper::convertNumberToWords($data['final_amount']);

            $authUser = Auth::user();
            $data['user_id'] = $authUser->id;
            $data['due_date'] = \Carbon\Carbon::now()->addDays(30)->toDateString();
            $data = array_map(function ($value) {
                return empty($value) && $value !== 0 ? null : $value;
            }, $data);
            $orderReceipt = $this->orderReceiptRepository->create($data);
            if (!isset($data['code'])) {
                $duplicateCodeExists = OrderReceipt::where('code', $orderReceipt->code)
                    ->where('id', '!=', $orderReceipt->id)
                    ->exists();

                if ($duplicateCodeExists) {
                    $this->orderReceiptRepository->delete($orderReceipt->id);
                    return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
                }
            }
            $orderReceiptId = $orderReceipt->id;

            foreach ($items as $itemData) {
                $itemData['orderReceipt_id'] = $orderReceiptId;
                $this->allItemRepository
                    ->setModel(OrderReceiptItem::class)->create($itemData);
            }

            return response()->json(['orderReceipt' => $orderReceipt, 'items' => $items], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Échec de la création du reçu de commande.', 'error' => $e->getMessage()], 500);
        }
    }


    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string',
            ]);

            $receipt = $this->orderReceiptRepository->find($id);

            if (!$receipt) {
                return response()->json(['message' => 'Reçu de commande introuvable.'], 404);
            }

            if ($receipt->status === 'Terminé') {
                return response()->json(['message' => 'Cette reçu de commande est déjà finalisée et ne peut pas être modifiée.'], 403);
            }

            $updated = $this->orderReceiptRepository->update($id, ['status' => $request->input('status')]);

            if (!$updated) {
                return response()->json(['message' => 'Échec de la mise à jour du statut.'], 500);
            }

            $updatedReceipts = $this->orderReceiptRepository->find($id);

            return response()->json([
                'message' => 'Statut mis à jour avec succès.',
                'updated' => [
                    'id' => $updatedReceipts->id,
                    'status' => $updatedReceipts->status,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur s\'est produite lors de la mise à jour du statut.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateDocument($id, Request $request)
    {
        try {
            $itemsIds = $request->input('ids', []);

            $allItems = [];
            $RefundNote = null;
            $totalAmount = 0;
            $isTaxable = false;

            $orderreceipt = $this->orderReceiptRepository
                ->getQuery()
                ->with(['client', 'user', 'items', 'quotes','orderReceiptQuotes'])
                ->find($id);

            if (!$orderreceipt) {
                return response()->json(['message' => "Reçu de commande avec l'ID {$id} introuvable"], 404);
            }
            if ($orderreceipt->status === "Terminé") {
                return response()->json(['message' => 'Reçu de commande Terminé '], 404);
            }

            if ($orderreceipt->status !== 'Payé') {
                return response()->json(['message' => 'La reçu de commande nest pas Payé et ne peut pas être traitée.'], 400);
            }

            $isTaxable = $orderreceipt->is_taxable;
            $orderreceiptAmount = 0;
            $quoteIds = [];

            // ✅ Check direct quote_id first
            if (!empty($orderreceipt->quote_id)) {
                $quoteIds[] = $orderreceipt->quote_id;
            } elseif ($orderreceipt->orderReceiptQuotes && $orderreceipt->orderReceiptQuotes->isNotEmpty()) {
                // ✅ Use pivot table if exists
                foreach ($orderreceipt->orderReceiptQuotes as $orderReceiptQuote) {
                    if (!in_array($orderReceiptQuote->quote_id, $quoteIds)) {
                        $quoteIds[] = $orderReceiptQuote->quote_id;
                    }
                }
            } else {
                // ✅ Fallback to items
                foreach ($orderreceipt->items->whereIn('id', $itemsIds) as $item) {
                    if ($item->quote_id && !in_array($item->quote_id, $quoteIds)) {
                        $quoteIds[] = $item->quote_id;
                    }
                }
            }

            $orderreceiptItems = collect($orderreceipt->items->whereIn('id', $itemsIds))->map(function ($item) use (&$orderreceiptAmount) {
                $discount = max(0, $item->discount ?? 0);

                $undiscountedAmount = $item->price * $item->quantity;
                $itemAmount = $undiscountedAmount - $discount;

                $orderreceiptAmount += $itemAmount;

                return [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'order' => $item->order,
                    'discount' => $discount,
                    'undiscounted_amount' => $undiscountedAmount,
                    'amount' => $itemAmount,
                    'quote_id' => $item->quote_id,
                ];
            });

            $totalAmount = $orderreceiptAmount;

            $taxAmount = $isTaxable ? $totalAmount * 0.2 : 0;
            $finalAmount = $totalAmount + $taxAmount;

            // ✅ Assign quote_id only if unique
            $finalQuoteId = (count($quoteIds) === 1) ? $quoteIds[0] : null;

            $data = [
                'quote_id' => $finalQuoteId,
                'amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'final_amount' => $finalAmount,
                'total_phrase' => NumberHelper::convertNumberToWords($finalAmount),
                'client_id' => $orderreceipt->client_id,
                'is_taxable' => $isTaxable,
                'status' => 'Brouillon',
                'refund_comment' => $orderreceipt->receipt_comment,
                'process_group_id' => $orderreceipt->process_group_id
            ];

            $authUser = Auth::user();
            $data['user_id'] = $authUser ? $authUser->id : null;

            if ($orderreceiptItems->isEmpty()) {
                return response()->json(['message' => 'Aucun article fourni pour créer une note de remboursement.'], 400);
            }

            $RefundNote = $this->RefundNoteRepository->create($data);

            if ($this->RefundNoteRepository->codeExists($RefundNote->code, $RefundNote->id)) {
                $this->RefundNoteRepository->delete($RefundNote->id);
                return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
            }

            // ✅ Pivot insertion only if multiple quote_ids
            if (count($quoteIds) > 1) {
                foreach ($quoteIds as $quoteId) {
                    // Prevent duplicates
                    if (!RefundNoteQuote::where('refund_note_id', $RefundNote->id)
                        ->where('quote_id', $quoteId)
                        ->exists()) {
                        RefundNoteQuote::create([
                            'refund_note_id' => $RefundNote->id,
                            'quote_id' => $quoteId
                        ]);
                    }
                }
            }

            foreach ($orderreceiptItems as $itemData) {
                $itemData['refund_note_id'] = $RefundNote->id;

                $this->allItemRepository
                    ->setModel(RefundItem::class)->create($itemData);
                $allItems[] = $itemData;
            }

            $this->orderReceiptRepository->update($id, ["status" => "Terminé"]);

            $response = [
                'refund' => $RefundNote,
                'items' => $allItems,
            ];

            // Include quote_ids in response if pivot entries were created
            if (count($quoteIds) > 1) {
                $response['refund_note_quotes'] = $quoteIds;
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

    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();
            if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
                return response()->json(['message' => 'Pas d\'articles fournis. La note de retour doit contenir au moins un article.'], 422);
            }
            $orderReceiptAmount = 0;

            $orderReceipt = $this->orderReceiptRepository->find($id);

            if (!$orderReceipt) {
                return response()->json(['message' => 'Facture introuvable.'], 404);
            }
            if ($orderReceipt->status === 'Validé' || $orderReceipt->status === 'Annulé' || $orderReceipt->status === 'Retourné' || $orderReceipt->status === 'Terminé') {
                return response()->json(['message' => 'Ce reçu de commande ne peut pas être modifié.'], 403);
            }
            if (isset($data['code']) && $data['code'] === $orderReceipt->code) {
                unset($data['code']);
            } else if (isset($data['code'])) {
                $validator = Validator::make($data, [
                    'code' => 'unique:order_receipts,code,' . $id,
                ]);

                if ($validator->fails()) {
                    return response()->json(['message' => 'Ce code est déjà utilisé par un autre document.'], 422);
                }
            }

            $existingItems = $orderReceipt->items;

            $requestItemIds = isset($data['items']) ? array_column($data['items'], 'id') : [];

            $items = [];

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (isset($itemData['delete']) && $itemData['delete'] === true) {
                        if (isset($itemData['id'])) {
                            $this->allItemRepository
                                ->setModel(OrderReceiptItem::class)->delete($itemData['id']);
                        }
                        continue;
                    }

                    $itemData['discount'] = isset($itemData['discount']) && is_numeric($itemData['discount'])
                        ? floatval($itemData['discount'])
                        : 0;

                    $itemData['undiscounted_amount'] = $itemData['price'] * $itemData['quantity'];
                    $itemData['amount'] = $itemData['undiscounted_amount'] - $itemData['discount'];

                    $orderReceiptAmount += $itemData['amount'];

                    // ADDED: Convert empty strings to null for nullable foreign key fields
                    if (isset($itemData['production_note_id']) && $itemData['production_note_id'] === '') {
                        $itemData['production_note_id'] = null;
                    }
                    if (isset($itemData['status']) && $itemData['status'] === '') {
                        $itemData['status'] = null;
                    }
                    if (isset($itemData['delivery_note_id']) && $itemData['delivery_note_id'] === '') {
                        $itemData['delivery_note_id'] = null;
                    }

                    if (isset($itemData['id']) && $itemData['id'] !== null && $itemData['id'] !== '') {
                        $this->allItemRepository
                            ->setModel(OrderReceiptItem::class)->update($itemData['id'], $itemData);
                    } else {
                        // FIXED: Changed from 'orderReceipt_id' to 'order_receipt_id' (snake_case)
                        $itemData['order_receipt_id'] = $id;
                        $this->allItemRepository
                            ->setModel(OrderReceiptItem::class)->create($itemData);
                    }

                    $items[] = $itemData;
                }
            }

            $itemsToDelete = array_diff(array_column($existingItems->toArray(), 'id'), $requestItemIds);

            foreach ($itemsToDelete as $itemId) {
                $this->allItemRepository
                    ->setModel(OrderReceiptItem::class)->delete($itemId);
            }

            // CHANGED: Remove items from data before sanitization
            unset($data['items']);

            $isTaxable = isset($data['is_taxable']) ? $data['is_taxable'] : $orderReceipt->is_taxable;

            $data['amount'] = $orderReceiptAmount;
            $data['tax_amount'] = $isTaxable ? $orderReceiptAmount * 0.2 : 0;
            $data['final_amount'] = $orderReceiptAmount + $data['tax_amount'];
            $data['total_phrase'] = NumberHelper::convertNumberToWords($data['final_amount']);

            // CHANGED: Only convert empty strings to null, preserve all other values including 0
            $data = array_map(function ($value) {
                return $value === '' ? null : $value;
            }, $data);

            $orderReceipt = $this->orderReceiptRepository->update($id, $data);

            return response()->json(['orderReceipts' => $orderReceipt, 'items' => $items]);
        } catch (\Exception $e) {
            return response()->json(['message' => "Une erreur s'est produite lors de la mise à jour du reçu de commande.", 'error' => $e->getMessage()], 500); // FIXED: changed second 'message' to 'error'
        }
    }
    public function delete($id)
    {
        $orderReceipt = $this->orderReceiptRepository->find($id);
        if (!$orderReceipt) {
            return response()->json(['message' => 'Reçu de commande introuvable.'], 404);
        }


        $this->orderReceiptRepository->delete($id);

        return response()->json(['message' => ' document supprimé avec succès.'], 200);
    }

    public function exportOrderReceipts(Request $request)
    {
        try {
            $filters = [
                'user_id' => $request->input('user'),
                'client_id' => $request->input('client'),
                'created_at' => $request->input('create'),
                'search' => $request->input('search'),
                'status' => $request->input('status'),
            ];

            $perPage = $request->input('per_page', 10);

            $fileName = 'order_receipts_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
            $filePath = storage_path('app/public/' . $fileName);


            $export = new OrderReceiptExport($filters, $perPage);

            Excel::store($export, 'public/' . $fileName);

            $downloadUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'Reçus de commande exportés avec succès.',
                'download_url' => $downloadUrl,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Échec de l exportation des reçus de commande : ' . $e->getMessage(),
            ], 500);
        }
    }
    public function generatePdf($id)
    {
        try {
            $orderReceipt = OrderReceipt::with(['client', 'user', 'items'])->find($id);

            if (!$orderReceipt) {
                return response()->json(['message' => 'Reçu de commande introuvable.'], 404);
            }


            $orderReceiptData = [
                'customer_name' => $orderReceipt->client->legal_name ?? 'N/A',
                'customer_address' => $orderReceipt->client->address ?? 'N/A',
                'total_in_words' => $orderReceipt->total_phrase ?? 'N/A',
                'comment' => $orderReceipt->note ?? 'N/A',
                'total_ht' => $orderReceipt->amount,
                'discount' => $orderReceipt->discount ?? 0,
                'tva' => $orderReceipt->tax_amount,
                'total_ttc' => $orderReceipt->final_amount,
                'validity_date' => $orderReceipt->due_date ?? 'N/A',
                'items' => $orderReceipt->items->map(function ($item) {
                    return [
                        'designation' => $item->description ?? 'N/A',
                        'price' => $item->price ?? 0,
                        'quantity' => $item->quantity ?? 0,
                        'total' => $item->amount ?? 0,
                    ];
                })->toArray(),
            ];

            $pdf = Pdf::loadView('pdf.order_receipt', ['orderReceipt' => $orderReceiptData]);

            $fileName = 'order_receipt_' . $orderReceipt->code . '_' . time() . '.pdf';


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
