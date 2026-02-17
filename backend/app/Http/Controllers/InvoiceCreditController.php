<?php

namespace App\Http\Controllers;

use App\Exports\InvoiceCreditExport;
use App\Helpers\FilterHelper;
use App\Helpers\NumberHelper;
use App\Models\InvoiceCredit;
use App\Models\InvoiceCreditItem;
use App\Models\RefundItem;
use App\Models\RefundNoteQuote;
use App\Repositories\Contracts\DocumentItemRepositoryInterface;
use App\Repositories\Contracts\InvoiceCreditRepositoryInterface;
use App\Repositories\Contracts\RefundNoteRepositoryInterface;
use App\Traits\HandlesConditionalRelationships;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceCreditController extends Controller
{
    use HandlesConditionalRelationships;

    protected $invoiceCreditRepository;
    protected $allItemRepository;
    protected $refundNoteRepository;


    public function __construct(InvoiceCreditRepositoryInterface $invoiceCreditRepository, DocumentItemRepositoryInterface $allItemRepository, RefundNoteRepositoryInterface $refundNoteRepository)
    {
        $this->invoiceCreditRepository = $invoiceCreditRepository;
        $this->allItemRepository = $allItemRepository;
        $this->refundNoteRepository = $refundNoteRepository;
    }

    public function getInvoiceCredits(Request $request, InvoiceCreditRepositoryInterface $repository)
    {
        try {
            $filters = [
                'user_id' => $request->input('user'),
                'client_id' => $request->input('client'),
                'created_at' => $request->input('create'),
                'search' => $request->input('search'),
                'quote_id' => $request->input('quote'),

                'archive' => $request->input('archive'),
                'status' => $request->input('status'),

            ];

            $perPage = $request->input('per_page', 10);
            if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
                $query = $repository->getInvoiceCredits()->with([
                    'client:id,legal_name',
                    'user:id,full_name',
                    'items'
                ])->onlyTrashed();
            } else {
                $query = $repository->getInvoiceCredits()->with(['client:id,legal_name', 'user:id,full_name', 'items']);
            }

            $filterableFields = ['user_id', 'client_id', 'created_at', 'quote_id', 'status'];
            $searchableFields = ['code', 'total_phrase'];

            $query = FilterHelper::applyFilters($query, $filters, $filterableFields);

            $query = FilterHelper::applySearch($query, $filters['search'], $searchableFields);

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $query->orderBy('created_at', 'desc');

            $invoiceCredits = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'current_page' => $invoiceCredits->currentPage(),
                'total_invoice_credits' => $invoiceCredits->total(),
                'per_page' => $invoiceCredits->perPage(),
                'invoice_credits' => $invoiceCredits
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }


    public function getInvoiceCreditsById(Request $request, $id)
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
                //     $query->select('id', 'status', 'code', "tax_amount", "quote_id", "is_taxable");
                // },
                // 'orderReceipts' => function ($query) {
                //     $query->select('id', 'status', 'code', "quote_id", "is_taxable");
                // },
                // 'invoiceCredits' => function ($query) {
                //     $query->select('id','client_id',  'status', 'code', 'tax_amount', 'quote_id', "is_taxable", "amount",'final_amount')
                //         ->with([
                //             'client:id,legal_name,ice,balance',
                //             'items' => function ($subQuery) {
                //             $subQuery->select('*')->orderBy('order');
                //         }])
                //         ->orderBy('code');
                // },
                'quotes' => function ($query) {
                    $query->select('id', 'status', 'code', "is_taxable");
                },
                'quoterequests' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                },
                'deliveryNotes' => function ($query) {
                    $query->select('id', 'status', 'code', "quote_id", "is_taxable");
                },
                // 'returnNotes' => function ($query) {
                //     $query->select('id', 'status', 'code', "quote_id", "is_taxable");
                // },
                // 'invoices' => function ($query) {
                //     $query->select('id', 'status', 'code', "quote_id", "is_taxable");
                // },
                'outputNotes' => function ($query) {
                    $query->select('id', 'status', 'code', "quote_id", "is_taxable");
                },
                'orderNotes' => function ($query) {
                    $query->select('id', 'status', 'code', "quote_id", "is_taxable");
                },
                'productionNotes' => function ($query) {
                    $query->select('id', 'status', 'code', "quote_id", "is_taxable");
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
                    $query->select('id', 'client_id',  'status', 'code', 'tax_amount', 'quote_id', "is_taxable", "amount", 'final_amount', 'process_group_id')
                        ->with([
                            'client:id,legal_name,ice,balance',
                            'items' => function ($subQuery) {
                                $subQuery->select('*')->orderBy('order');
                            }
                        ])
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
                $invoiceCredit = $this->invoiceCreditRepository->getInvoiceCredits()->onlyTrashed() ->with($relationships)->find($id);
            } else {
                $invoiceCredit = $this->invoiceCreditRepository->getInvoiceCredits()->with($relationships)->find($id);
            }


            if (!$invoiceCredit) {
                return response()->json(['message' => 'Introuvable.'], 404);
            }

            $invoiceCredit = $this->mergeConditionalRelationships($invoiceCredit);


            return response()->json($invoiceCredit);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function create(Request $request)
    {
        try {

            $data = $request->all();

            if (!isset($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
                return response()->json(['message' => 'Aucun article fourni.'], 400);
            }

            $invoiceCreditAmount = 0;
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

                $invoiceCreditAmount += $itemData['amount'];

                $items[] = $itemData;
            }

            $data['amount'] =  $invoiceCreditAmount;

            $data['tax_amount'] = $data['is_taxable'] ?  $invoiceCreditAmount * 0.2 : 0;
            $data['final_amount'] =  $invoiceCreditAmount + $data['tax_amount'];
            $data['total_phrase'] = NumberHelper::convertNumberToWords($data['final_amount']);

            $authUser = Auth::user();
            $data['user_id'] = $authUser->id;
            $data['due_date'] = \Carbon\Carbon::now()->addDays(30)->toDateString();
            $data = array_map(function ($value) {
                // Only convert truly empty values to null
                if ($value === '' || (empty($value) && $value !== 0 && $value !== '0' && $value !== false)) {
                    return null;
                }
                return $value;
            }, $data);

            $invoiceCredit = $this->invoiceCreditRepository->create($data);
            if (!isset($data['code'])) {
                $duplicateCodeExists = InvoiceCredit::where('code', $invoiceCredit->code)
                    ->where('id', '!=', $invoiceCredit->id)
                    ->exists();

                if ($duplicateCodeExists) {
                    $this->invoiceCreditRepository->delete($invoiceCredit->id);
                    return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
                }
            }
            $invoiceCreditId =  $invoiceCredit->id;

            foreach ($items as $itemData) {
                $itemData[' invoice_credit_id'] =  $invoiceCreditId;
                $this->allItemRepository
                    ->setModel(InvoiceCreditItem::class)->create($itemData);
            }

            return response()->json([' invoiceCredit' =>  $invoiceCredit, 'items' => $items], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Échec de la création de l avoir.', 'error' => $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();
            if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
                return response()->json(['message' => 'Pas d\'articles fournis. La note de retour doit contenir au moins un article.'], 422);
            }
            $Amount = 0;

            $invoiceCredit = $this->invoiceCreditRepository->findById($id);

            if (!$invoiceCredit) {
                return response()->json(['message' => 'Facture introuvable.'], 404);
            }
            if (isset($data['code']) && $data['code'] === $invoiceCredit->code) {
                unset($data['code']);
            } else if (isset($data['code'])) {
                $validator = Validator::make($data, [
                    'code' => 'unique:invoice_credits,code,' . $id,
                ]);

                if ($validator->fails()) {
                    return response()->json(['message' => 'Ce code est déjà utilisé par un autre document.'], 422);
                }
            }
            $existingItems = $invoiceCredit->items;

            $requestItemIds = isset($data['items']) ? array_column($data['items'], 'id') : [];

            $items = [];

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (isset($itemData['delete']) && $itemData['delete'] === true) {
                        if (isset($itemData['id'])) {
                            $this->allItemRepository
                                ->setModel(InvoiceCreditItem::class)->delete($itemData['id']);
                        }
                        continue;
                    }

                    $itemData['discount'] = isset($itemData['discount']) && is_numeric($itemData['discount'])
                        ? floatval($itemData['discount'])
                        : 0;

                    $itemData['undiscounted_amount'] = $itemData['price'] * $itemData['quantity'];
                    $itemData['amount'] = $itemData['undiscounted_amount'] - $itemData['discount'];

                    $Amount += $itemData['amount'];

                    if (isset($itemData['id']) && $itemData['id'] !== null && $itemData['id'] !== '') {
                        $this->allItemRepository
                            ->setModel(InvoiceCreditItem::class)->update($itemData['id'], $itemData);
                    } else {
                        $itemData['order_receipt_id'] = $id;
                        $this->allItemRepository
                            ->setModel(InvoiceCreditItem::class)->create($itemData);
                    }

                    $items[] = $itemData;
                }
            }

            $itemsToDelete = array_diff(array_column($existingItems->toArray(), 'id'), $requestItemIds);

            foreach ($itemsToDelete as $itemId) {
                $this->allItemRepository
                    ->setModel(InvoiceCreditItem::class)->delete($itemId);
            }


            $isTaxable = isset($data['is_taxable']) ? $data['is_taxable'] : $invoiceCredit->is_taxable;

            $data['amount'] = $Amount;
            $data['tax_amount'] = $isTaxable ? $Amount * 0.2 : 0;
            $data['final_amount'] = $Amount + $data['tax_amount'];
            $data['total_phrase'] = NumberHelper::convertNumberToWords($data['final_amount']);
            $data = array_map(function ($value) {
                return empty($value) && $value !== 0 ? null : $value;
            }, $data);
            $orderReceipt = $this->invoiceCreditRepository->update($id, $data);

            return response()->json(['orderReceipt' => $orderReceipt, 'items' => $items]);
        } catch (\Exception $e) {
            return response()->json(['message' => "Une erreur s'est produite lors de la mise à jour du reçu de commande.", 'error' => $e->getMessage()], 500);
        }
    }


    public function delete($id)
    {
        $doc = $this->invoiceCreditRepository->findById($id);



        $this->invoiceCreditRepository->delete($id);

        return response()->json(['message' => ' document supprimé avec succès.'], 200);
    }
    public function exportInvoiceCredits(Request $request)
    {
        try {
            $filters = [
                'client_id' => $request->input('client'),
                'status' => $request->input('status'),
                'search' => $request->input('search'),
                'date_range' => $request->input('range'),

            ];

            $perPage = $request->input('per_page', 10);

            $fileName = 'invoice_credits_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
            $filePath = storage_path('app/public/' . $fileName);

            $export = new InvoiceCreditExport($filters, $perPage);

            Excel::store($export, 'public/' . $fileName);

            $downloadUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'Avoirs exportés avec succès.',
                'download_url' => $downloadUrl,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Échec de l exportation des avoirs.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string',
            ]);

            $invoiceCredit = $this->invoiceCreditRepository->findById($id);

            if (!$invoiceCredit) {
                return response()->json(['message' => 'Facture d\'avoir introuvable.'], 404);
            }

            if ($invoiceCredit->status === 'Terminé') {
                return response()->json(['message' => 'Cette facture d\'avoir est déjà finalisée et ne peut pas être modifiée.'], 403);
            }

            $updated = $this->invoiceCreditRepository->update($id, ['status' => $request->input('status')]);

            if (!$updated) {
                return response()->json(['message' => 'Échec de la mise à jour du statut.'], 500);
            }

            $updatedInvoiceCredit = $this->invoiceCreditRepository->findById($id);

            return response()->json([
                'message' => 'Statut mis à jour avec succès.',
                'invoice_credit' => [
                    'id' => $updatedInvoiceCredit->id,
                    'status' => $updatedInvoiceCredit->status,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Une erreur s'est produite lors de la mise à jour du statut.",
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function generatePdf($id)
    {
        try {
            $invoiceCredit = $this->invoiceCreditRepository->getInvoiceCredits()->with(['client', 'user', 'items'])->find($id);

            if (!$invoiceCredit) {
                return response()->json(['message' => 'Crédit de facture introuvable'], 404);
            }

            $Data = [
                'customer_name' => $invoiceCredit->client->legal_name ?? 'N/A',
                'customer_address' => $invoiceCredit->client->address ?? 'N/A',
                'total_in_words' => $invoiceCredit->total_phrase,
                'comment' => $invoiceCredit->delivery_comment,
                'total_ht' => $invoiceCredit->amount,
                'discount' => $invoiceCredit->discount ?? 0,
                'tva' => $invoiceCredit->tax_amount,
                'total_ttc' => $invoiceCredit->final_amount,
                'validity_date' => $invoiceCredit->validity_date ?? 'N/A',
                'items' => $invoiceCredit->items->map(function ($item) {
                    return [
                        'designation' => $item->description,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'total' => $item->amount,
                    ];
                })->toArray(),
            ];

            $pdf = Pdf::loadView('pdf.invoice_credit', ['invoiceCredit' => $Data]);

            $fileName = 'invoice_credit_' . $invoiceCredit->id . '_' . time() . '.pdf';

            $pdf->save(storage_path('app/public/' . $fileName));


            $publicUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'PDF généré avec succès.',
                'download_url' => $publicUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => " Une erreur s'est produite.",
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function generateDocument($id)
    {
        try {
            $invoiceCredit = $this->invoiceCreditRepository->getInvoiceCredits()
                ->with(['client', 'user', 'items', 'quotes', 'invoiceCreditQuotes'])
                ->find($id);

            if (!$invoiceCredit) {
                return response()->json(['message' => 'Facture avoir introuvable'], 404);
            }

            $authUser = Auth::user();

            // ✅ Step 1: Initialize quote IDs array
            $quoteIds = [];

            // ✅ Step 2: Prefer direct quote_id if available
            if (!empty($invoiceCredit->quote_id)) {
                $quoteIds[] = $invoiceCredit->quote_id;
            } else {
                // ✅ Step 3: Otherwise, collect from pivot or items
                if ($invoiceCredit->invoiceCreditQuotes && $invoiceCredit->invoiceCreditQuotes->isNotEmpty()) {
                    foreach ($invoiceCredit->invoiceCreditQuotes as $invoiceCreditQuote) {
                        if (!in_array($invoiceCreditQuote->quote_id, $quoteIds)) {
                            $quoteIds[] = $invoiceCreditQuote->quote_id;
                        }
                    }
                } else {
                    // If no pivot table data, collect from items
                    foreach ($invoiceCredit->items as $item) {
                        if ($item->quote_id && !in_array($item->quote_id, $quoteIds)) {
                            $quoteIds[] = $item->quote_id;
                        }
                    }
                }
            }

            // ✅ Step 4: Determine if we have one or multiple quotes
            $finalQuoteId = (count($quoteIds) === 1) ? $quoteIds[0] : null;

            // ✅ Step 5: Create Refund Note
            $data = [
                'quote_id' => $finalQuoteId, // may be null if multiple quotes
                'amount' => $invoiceCredit->amount,
                'tax_amount' => $invoiceCredit->tax_amount,
                'final_amount' => $invoiceCredit->final_amount,
                'total_phrase' => $invoiceCredit->total_phrase,
                'client_id' => $invoiceCredit->client_id,
                'is_taxable' => $invoiceCredit->is_taxable,
                'status' => 'Brouillon',
                'refund_comment' => $invoiceCredit->credit_comment,
                'user_id' => $authUser ? $authUser->id : null,
                'invoice_credit_id' => $invoiceCredit->id,
                'process_group_id' => $invoiceCredit->process_group_id
            ];

            $refundNote = $this->refundNoteRepository->create($data);

            // ✅ Check code uniqueness
            if ($this->refundNoteRepository->codeExists($refundNote->code, $refundNote->id)) {
                $this->refundNoteRepository->delete($refundNote->id);
                return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
            }

            // ✅ Step 6: Create pivot entries only if needed
            // (when there are multiple quotes or no direct quote_id in invoice_credit)
            if (empty($invoiceCredit->quote_id) && count($quoteIds) > 1) {
                foreach ($quoteIds as $quoteId) {
                    RefundNoteQuote::create([
                        'refund_note_id' => $refundNote->id,
                        'quote_id' => $quoteId
                    ]);
                }
            }

            // ✅ Step 7: Duplicate items
            $items = $invoiceCredit->items->map(function ($item) use ($refundNote) {
                return [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'discount' => $item->discount,
                    'undiscounted_amount' => $item->undiscounted_amount,
                    'amount' => $item->amount,
                    'refund_note_id' => $refundNote->id,
                    'order' => $item->order,
                    'status' => $item->status,
                    'production_note_id' => $item->production_note_id,
                    'quote_id' => $item->quote_id,
                ];
            });

            foreach ($items as $itemData) {
                $this->allItemRepository
                    ->setModel(RefundItem::class)
                    ->create($itemData);
            }

            // ✅ Step 8: Update status
            $this->invoiceCreditRepository->update($id, ["status" => "Soldé"]);

            // ✅ Step 9: Response
            $response = [
                'refund' => $refundNote,
                'items' => $items
            ];

            // Include pivot quote IDs in response if multiple were used
            if (empty($invoiceCredit->quote_id) && count($quoteIds) > 1) {
                $response['refund_note_quotes'] = $quoteIds;
            }

            return response()->json($response, 200);

        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            $status = ($code >= 100 && $code <= 599) ? $code : 500;

            return response()->json([
                'status' => $status,
                'message' => $e->getMessage(),
            ], $status);
        }
    }

}
