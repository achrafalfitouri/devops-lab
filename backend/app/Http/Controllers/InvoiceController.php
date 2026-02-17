<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Exports\InvoiceExport;
use App\Helpers\FilterHelper;
use App\Helpers\NumberHelper;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\Invoice;
use App\Models\InvoiceCreditItem;
use App\Models\InvoiceCreditQuote;
use App\Models\InvoiceItem;
use App\Repositories\Contracts\DocumentItemRepositoryInterface;
use App\Repositories\Contracts\InvoiceCreditRepositoryInterface;
use App\Traits\HandlesConditionalRelationships;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceController extends Controller
{
    use HandlesConditionalRelationships;

    protected $invoiceRepository;
    protected $allItemRepository;
    protected $invoiceCreditRepository;
    protected $invoiceCreditItemRepository;

    public function __construct(InvoiceRepositoryInterface $invoiceRepository, DocumentItemRepositoryInterface $allItemRepository, InvoiceCreditRepositoryInterface $invoiceCreditRepository,)
    {
        $this->invoiceRepository = $invoiceRepository;
        $this->allItemRepository = $allItemRepository;
        $this->invoiceCreditRepository = $invoiceCreditRepository;
    }


    public function getInvoices(Request $request)
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
                $query = $this->invoiceRepository->getInvoices()->with([
                    'client:id,legal_name',
                    'user:id,full_name',
                    'items'
                ])->onlyTrashed();
            } else {
                $query = $this->invoiceRepository->getInvoices()->with(['client:id,legal_name,balance', 'user:id,full_name', 'items']);
            }


            $filterableFields = ['user_id', 'client_id', 'quote_id', 'status'];
            $searchableFields = ['code', 'id'];

            $query = FilterHelper::applyFilters($query, $filters, $filterableFields);

            $query = FilterHelper::applySearch($query, $filters['search'], $searchableFields);

            if (!empty($filters['created_at'])) {
                $query->whereDate('created_at', $filters['created_at']);
            }
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            $query->orderBy('created_at', 'desc');
            $invoices = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'current_page' => $invoices->currentPage(),
                'total_invoices' => $invoices->total(),
                'per_page' => $invoices->perPage(),
                'invoices' => $invoices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }



    public function getInvoiceById(Request $request, $id)
    {
        try {
            $isArchived = filter_var($request->query('archive'), FILTER_VALIDATE_BOOLEAN);

            $baseRelationships = [
                'client:id,legal_name,ice,balance',
                'user:id,full_name,code',

                'items' => function ($query) {
                    $query->select("*")->orderBy('order');
                },
                'payments' => function ($query) {
                    $query->select('payments.id', 'payments.code', 'payments.recovery_id')
                          ->with('recovery:id,code,recovery_balance,amount');
                },
                // 'refunds' => function ($query) {
                //     $query->select('id', 'status', 'code', "tax_amount", "quote_id", "is_taxable");
                // },
                // 'orderReceipts' => function ($query) {
                //     $query->select('id', 'status', 'code', "quote_id", "is_taxable");
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
                // 'invoiceCredits' => function ($query) {
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
                },
                // 'invoices' => function ($query) {
                //     $query->select('id', 'client_id', 'status', 'code', 'tax_amount', 'quote_id', "is_taxable", "amount", "payed_amount", "final_amount")
                //         ->with([
                //             'client:id,legal_name,ice,balance',
                //             'items' => function ($subQuery) {
                //                 $subQuery->select('*')->orderBy('order');
                //             }
                //         ])
                //         ->orderBy('code');
                // }
            ];

            $conditionalRelationships = [
                'invoices' => function ($query) {
                    $query->select('id', 'client_id', 'status', 'code', 'tax_amount', 'quote_id', "is_taxable", "amount", "payed_amount", "final_amount", 'process_group_id')
                        ->with([
                            'client:id,legal_name,ice,balance',
                            'items' => function ($subQuery) {
                                $subQuery->select('*')->orderBy('order');
                            }
                        ])
                        ->orderBy('code');
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

            $query = $this->invoiceRepository->getInvoices();

            if ($isArchived) {
                $query = $query->onlyTrashed();
            }

            $invoice = $query->with($relationships)->find($id);

            if (!$invoice) {
                return response()->json(['message' => 'Invoice not found'], 404);
            }
            $invoice = $this->mergeConditionalRelationships($invoice);

            return response()->json($invoice);
        } catch (\Exception $e) {
            $statusCode = is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599
                ? $e->getCode()
                : 500;
                
            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    public function create(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
                return response()->json(['message' => 'Aucun article fourni'], 400);
            }

            $invoiceAmount = 0;
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

                $invoiceAmount += $itemData['amount'];

                $items[] = $itemData;
            }

            $data['amount'] = $invoiceAmount;

            $data['tax_amount'] = $data['is_taxable'] ? $invoiceAmount * 0.2 : 0;
            $data['final_amount'] = $invoiceAmount + $data['tax_amount'];
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

            $invoice = $this->invoiceRepository->create($data);
            if (!isset($data['code'])) {
                $duplicateCodeExists = Invoice::where('code', $invoice->code)
                    ->where('id', '!=', $invoice->id)
                    ->exists();

                if ($duplicateCodeExists) {
                    $this->invoiceRepository->delete($invoice->id);
                    return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
                }
            }
            $invoiceId = $invoice->id;

            foreach ($items as $itemData) {
                $itemData['invoice_id'] = $invoiceId;
                $this->allItemRepository
                    ->setModel(InvoiceItem::class)->create($itemData);
            }

            return response()->json(['invoice' => $invoice, 'items' => $items], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Échec de la création de la facture.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();
            if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
                return response()->json(['message' => 'Pas d\'articles fournis. La note de retour doit contenir au moins un article.'], 422);
            }
            $invoiceAmount = 0;

            $invoice = $this->invoiceRepository->findById($id);

            if (!$invoice) {
                throw new ModelNotFoundException('Bon de livraison introuvable');
            }
            if ($invoice->status === 'Validé' || $invoice->status === 'Annulé' || $invoice->status === 'Retourné' || $invoice->status === 'Terminé') {
                return response()->json(['message' => 'Ce invoice ne peut pas être modifié'], 403);
            }
            if (isset($data['code']) && $data['code'] === $invoice->code) {
                unset($data['code']);
            } else if (isset($data['code'])) {
                $validator = Validator::make($data, [
                    'code' => 'unique:invoices,code,' . $id,
                ]);

                if ($validator->fails()) {
                    return response()->json(['message' => 'Ce code est déjà utilisé par un autre document.'], 422);
                }
            }
            $existingItems = $invoice->items;

            $requestItemIds = isset($data['items']) ? array_column($data['items'], 'id') : [];

            $items = [];

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (isset($itemData['delete']) && $itemData['delete'] === true) {
                        if (isset($itemData['id'])) {
                            $this->allItemRepository // FIXED: removed double $this->
                                ->setModel(InvoiceItem::class)->delete($itemData['id']);
                        }
                        continue;
                    }

                    $itemData['discount'] = isset($itemData['discount']) && is_numeric($itemData['discount'])
                        ? floatval($itemData['discount'])
                        : 0;

                    $itemData['undiscounted_amount'] = $itemData['price'] * $itemData['quantity'];
                    $itemData['amount'] = $itemData['undiscounted_amount'] - $itemData['discount'];

                    $invoiceAmount += $itemData['amount'];

                    // ADDED: Convert empty strings to null for nullable foreign key fields
                    if (isset($itemData['production_note_id']) && $itemData['production_note_id'] === '') {
                        $itemData['production_note_id'] = null;
                    }
                    if (isset($itemData['status']) && $itemData['status'] === '') {
                        $itemData['status'] = null;
                    }

                    if (isset($itemData['id']) && $itemData['id'] !== null && $itemData['id'] !== '') {
                        $this->allItemRepository
                            ->setModel(InvoiceItem::class)->update($itemData['id'], $itemData);
                    } else {
                        $itemData['invoice_id'] = $id;
                        $this->allItemRepository
                            ->setModel(InvoiceItem::class)->create($itemData);
                    }

                    $items[] = $itemData;
                }
            }

            $itemsToDelete = array_diff(array_column($existingItems->toArray(), 'id'), $requestItemIds);

            foreach ($itemsToDelete as $itemId) {
                $this->allItemRepository
                    ->setModel(InvoiceItem::class)->delete($itemId);
            }

            // CHANGED: Remove items from data before sanitization
            unset($data['items']);

            $isTaxable = isset($data['is_taxable']) ? $data['is_taxable'] : $invoice->is_taxable;

            $data['amount'] = $invoiceAmount;
            $data['tax_amount'] = $isTaxable ? $invoiceAmount * 0.2 : 0;
            $data['final_amount'] = $invoiceAmount + $data['tax_amount'];
            $data['total_phrase'] = NumberHelper::convertNumberToWords($data['final_amount']);

            // CHANGED: Only convert empty strings to null, preserve all other values
            $data = array_map(function ($value) {
                return $value === '' ? null : $value;
            }, $data);

            $invoice = $this->invoiceRepository->update($id, $data);

            return response()->json(['invoice' => $invoice, 'items' => $items]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Une erreur sest produite lors de la mise à jour de la facture.', 'error' => $e->getMessage()], 500); // FIXED: changed second 'message' to 'error'
        }
    }


    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string',
            ]);

            $invoice = $this->invoiceRepository->findById($id);

            if (!$invoice) {
                return response()->json(['message' => 'Facture introuvable.'], 404);
            }

            if ($invoice->status === 'Terminé') {
                return response()->json(['message' => 'Cette facture est déjà finalisée et ne peut pas être modifiée.'], 403);
            }

            $updated = $this->invoiceRepository->update($id, ['status' => $request->input('status')]);

            if (!$updated) {
                return response()->json(['message' => 'Échec de la mise à jour du statut.'], 500);
            }

            $updatedInvoice = $this->invoiceRepository->findById($id);

            return response()->json([
                'message' => 'Statut mis à jour avec succès.',
                'invoice' => [
                    'id' => $updatedInvoice->id,
                    'status' => $updatedInvoice->status,
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
            $invoiceCredit = null;
            $totalAmount = 0;
            $isTaxable = false;

            $invoice = $this->invoiceRepository
                ->getInvoices()
                ->with(['client', 'user', 'items', 'quotes', 'invoiceQuotes'])
                ->find($id);

            if (! $invoice) {
                return response()->json(['message' => "Facture avec l'ID {$id} introuvable"], 404);
            }
            if ($invoice->status === "Terminé") {
                return response()->json(['message' => 'Facture Terminé '], 404);
            }

            if ($invoice->status !== 'Payé') {
                return response()->json(['message' => 'La facture n\'est pas Payé et ne peut pas être traitée.'], 400);
            }

            $isTaxable = $invoice->is_taxable;
            $invoiceAmount = 0;
            $quoteIds = []; // Track unique quote IDs
            $finalQuoteId = null;

            // ✅ PRIORITY 1: Check if invoice has quote_id directly
            if ($invoice->quote_id) {
                // If invoice has quote_id, use it directly - no pivot needed
                $finalQuoteId = $invoice->quote_id;
            } else {
                // ✅ PRIORITY 2: Get quote_ids from invoice's pivot table (InvoiceQuote) OR from items
                if ($invoice->invoiceQuotes && $invoice->invoiceQuotes->isNotEmpty()) {
                    foreach ($invoice->invoiceQuotes as $invoiceQuote) {
                        // Only add non-null quote_ids
                        if ($invoiceQuote->quote_id && !in_array($invoiceQuote->quote_id, $quoteIds)) {
                            $quoteIds[] = $invoiceQuote->quote_id;
                        }
                    }
                } else {
                    // If no pivot table data, collect from items
                    foreach ($invoice->items->whereIn('id', $itemsIds) as $item) {
                        // Only add non-null quote_ids
                        if ($item->quote_id && !in_array($item->quote_id, $quoteIds)) {
                            $quoteIds[] = $item->quote_id;
                        }
                    }
                }

                // Determine quote_id based on validity and uniqueness
                // If we have exactly 1 non-null quote_id -> store directly in invoice_credit
                // Otherwise -> set to null and use pivot table (if we have valid quote_ids)
                $finalQuoteId = (count($quoteIds) === 1) ? $quoteIds[0] : null;
            }

            $invoiceItems = collect($invoice->items->whereIn('id', $itemsIds))->map(function ($item) use (&$invoiceAmount) {
                $discount = max(0, $item->discount ?? 0);

                $undiscountedAmount = $item->price * $item->quantity;
                $itemAmount = $undiscountedAmount - $discount;

                $invoiceAmount += $itemAmount;

                return [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'order' => $item->order,
                    'discount' => $discount,
                    'undiscounted_amount' => $undiscountedAmount,
                    'amount' => $itemAmount,
                    'production_note_id' => $item->production_note_id,
                    'quote_id' => $item->quote_id,
                ];
            });

            $totalAmount = $invoiceAmount;

            $taxAmount = $isTaxable ? $totalAmount * 0.2 : 0;
            $finalAmount = $totalAmount + $taxAmount;

            $data = [
                'quote_id' => $finalQuoteId,
                'amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'final_amount' => $finalAmount,
                'total_phrase' => NumberHelper::convertNumberToWords($finalAmount),
                'client_id' => $invoice->client_id,
                'is_taxable' => $isTaxable,
                'status' => 'Brouillon',
                'credit_comment' => $invoice->invoice_comment,
                'invoice_id' => $invoice->id,
                'process_group_id' => $invoice->process_group_id
            ];

            $authUser = Auth::user();
            $data['user_id'] = $authUser ? $authUser->id : null;

            $invoiceCredit = $this->invoiceCreditRepository->create($data);

            if ($this->invoiceCreditRepository->codeExists($invoiceCredit->code, $invoiceCredit->id)) {
                $this->invoiceCreditRepository->delete($invoiceCredit->id);
                return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
            }

            // ✅ Only create pivot entries if:
            // - quote_id was NOT stored directly (is null in invoice_credit)
            // - AND we have at least one valid (non-null) quote_id
            // - AND invoice didn't have a direct quote_id
            if (!$finalQuoteId && count($quoteIds) > 0) {
                foreach ($quoteIds as $quoteId) {
                    InvoiceCreditQuote::create([
                        'invoice_credit_id' => $invoiceCredit->id,
                        'quote_id' => $quoteId
                    ]);
                }
            }

            // Create invoice credit items
            foreach ($invoiceItems as $itemData) {
                $itemData['invoice_credit_id'] = $invoiceCredit->id;

                $this->allItemRepository
                    ->setModel(InvoiceCreditItem::class)->create($itemData);
                $allItems[] = $itemData;
            }

            $this->invoiceRepository->update($id, ["status" => "Terminé"]);

            $response = [
                'invoice_credit' => $invoiceCredit,
                'items' => $allItems,
            ];

            // ✅ Include quote_ids in response only if pivot entries were created
            if (!$finalQuoteId && count($quoteIds) > 0) {
                $response['invoice_credit_quotes'] = $quoteIds;
            }

            return response()->json($response, 201);
        } catch (\Exception $e) {
            // Properly convert exception code to valid HTTP status code
            $statusCode = is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599
                ? $e->getCode()
                : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }
    public function exportInvoices(Request $request)
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

            $fileName = 'invoices_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

            $export = new InvoiceExport($filters, $perPage);

            Excel::store($export, 'public/' . $fileName);

            $downloadUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'Factures exportées avec succès.',
                'download_url' => $downloadUrl,
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Échec de l exportation des factures.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function generatePdf($id)
    {
        $invoice = $this->invoiceRepository->getInvoices()->with(['client', 'user', 'items'])->find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Facture introuvable'], 404);
        }

        $invoiceData = [
            'customer_name' => $invoice->client->legal_name ?? 'N/A',
            'customer_address' => $invoice->client->address ?? 'N/A',
            'total_in_words' => $invoice->total_phrase,
            'comment' => $invoice->delivery_comment,
            'total_ht' => $invoice->amount,
            'discount' => $invoice->discount ?? 0,
            'tva' => $invoice->tax_amount,
            'total_ttc' => $invoice->final_amount,
            'validity_date' => $invoice->validity_date ?? 'N/A',
            'items' => $invoice->items->map(function ($item) {
                return [
                    'designation' => $item->description,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'total' => $item->amount,
                ];
            })->toArray(),
        ];

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoiceData]);

        $fileName = 'invoice_' . $invoice->id . '_' . time() . '.pdf';

        $pdf->save(storage_path('app/public/' . $fileName));

        $publicUrl = asset('storage/' . $fileName);
        return response()->json([
            'status' => 200,
            'message' => 'PDF a généré avec succès.',
            'download_url' => $publicUrl,
        ]);
    }

    public function delete($id)
    {
        $doc = $this->invoiceRepository->findById($id);

        if (!$doc) {
            return response()->json(['message' => 'Facture introuvable.'], 404);
        }

        // Update related delivery notes status to 'Brouillon'
        DeliveryNote::where('invoice_id', $id)->update(['status' => 'Brouillon']);
    
        $this->invoiceRepository->delete($id);

        return response()->json(['message' => 'Document supprimé avec succès.'], 200);
    }
}
