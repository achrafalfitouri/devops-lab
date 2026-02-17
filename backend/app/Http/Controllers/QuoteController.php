<?php

namespace App\Http\Controllers;

use App\Exports\QuoteExport;
use App\Helpers\FilterHelper;
use App\Helpers\NumberHelper;
use App\Models\Client;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\InvoiceCredit;
use App\Models\OrderNote;
use App\Models\OrderNoteItem;
use App\Models\OrderReceipt;
use App\Models\OutputNote;
use App\Models\ProductionNote;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuoteRequest;
use App\Models\RefundNote;
use App\Models\ReturnNote;
use App\Repositories\Contracts\DocumentItemRepositoryInterface;
use App\Repositories\Contracts\OrderNoteRepositoryInterface;
use App\Repositories\Contracts\QuoteRepositoryInterface;
use App\Services\ItemLogger;
use App\Traits\HandlesConditionalRelationships;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;


class QuoteController extends Controller
{

    use HandlesConditionalRelationships;

    protected $quoteRepository;
    protected $orderNoteRepository;
    protected $orderNoteItemRepository;
    protected $allItemRepository;
    protected $itemLogger;


    public function __construct(
        QuoteRepositoryInterface $quoteRepository,
        OrderNoteRepositoryInterface $orderNoteRepository,
        DocumentItemRepositoryInterface $allItemRepository
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->orderNoteRepository = $orderNoteRepository;
        $this->allItemRepository = $allItemRepository;
        $this->itemLogger = new ItemLogger();
    }


    public function getQuotes(Request $request, QuoteRepositoryInterface $repository)
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
                $query = $repository->getQuotes()->with([
                    'client:id,legal_name',
                    'user:id,full_name',
                    'items',
                ])->onlyTrashed();
            } else {
                $query = $repository->getQuotes()->with([
                    'client:id,legal_name',
                    'user:id,full_name',
                    'items',
                ]);
            }


            $filterableFields = ['user_id', 'client_id', 'status'];
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
            $quotes = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'current_page' => $quotes->currentPage(),
                'total_quotes' => $quotes->total(),
                'per_page' => $quotes->perPage(),
                'quotes' => $quotes

            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ], is_int($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }




    public function getQuoteById(Request $request, $id)
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
                    $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                },
                'quoterequests' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                },
                'outputNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                },
                // 'refunds' => function ($query) {
                //     $query->select('id', 'status', 'code', "tax_amount", "quote_id", "is_taxable");
                // },
                'deliveryNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                },
                // 'returnNotes' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                // },
                // 'invoices' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                // },
                // 'orderReceipts' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                // },
                // 'invoiceCredits' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                // },
                'productionNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                },
                'quotes' => function ($query) {
                    $query->select('id', 'client_id',  'status', 'amount', 'code', "tax_amount", "is_taxable", 'final_amount')
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
                $quote = $this->quoteRepository->getQuotes()->onlyTrashed()->with($relationships)->find($id);
            } else {
                $quote = $this->quoteRepository->getQuotes()->with($relationships)->find($id);
            }

            if (!$quote) {
                return response()->json(['message' => 'Devis introuvable'], 404);
            }
            $quote = $this->mergeConditionalRelationships($quote);

            return response()->json($quote);
        } catch (\Exception $e) {
            return response()->json([
                'status' =>  500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'validity_date' => 'nullable|date|after_or_equal:today',
                'amount' => 'nullable|numeric|min:0',
                'is_taxable' => 'required|boolean',
                'tax_amount' => 'nullable|numeric|min:0',
                'final_amount' => 'nullable|numeric|min:0',
                'total_phrase' => 'nullable|string|max:255',
                'quote_comment' => 'nullable|string|max:500',
                'status' => 'nullable|string|in:Brouillon,Validé,Annulé',
                'client_id' => 'required|exists:clients,id',
                'user_id' => 'nullable|exists:users,id',

                'items' => 'required|array|min:1',
                'items.*.description' => 'nullable|string|max:255',
                'items.*.price' => 'required|numeric|min:0',
                'items.*.quantity' => 'required|numeric|min:0',
                'items.*.undiscounted_amount' => 'nullable|numeric|min:0',
                'items.*.discount' => 'nullable|numeric|min:0',
                'items.*.amount' => 'nullable|numeric|min:0',
                'items.*.order' => 'nullable|integer',
                'items.*.status' => 'nullable|string|in:Validé,Brouillon,Annulé',
            ]);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first(), 422);
            }
            $data = $request->all();

            if (!isset($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
                return response()->json(['message' => 'Aucun élément fourni.'], 400);
            }
            $client = Client::find($data['client_id']);
            if (!$client) {
                return response()->json(['message' => 'Client introuvable.'], 404);
            }
            if ($client->legal_name === 'Client de passage') {
                return response()->json(['message' => 'Impossible de créer un devis pour ce client.'], 403);
            }
            $minValidityDate = Carbon::now()->addDays(15)->toDateString();
            $data['validity_date'] = $data['validity_date'] ?? $minValidityDate;


            $quoteAmount = 0;
            $items = [];

            foreach ($data['items'] as $itemData) {
                if (!isset($itemData['price'], $itemData['quantity'], $itemData['discount'])) {
                    return response()->json(['message' => 'Champ prix, quantité ou remise manquant dans les éléments.'], 400);
                }

                $itemData['discount'] = isset($itemData['discount']) && is_numeric($itemData['discount'])
                    ? floatval($itemData['discount'])
                    : 0;

                $itemData['undiscounted_amount'] = $itemData['price'] * $itemData['quantity'];
                $itemData['amount'] = $itemData['undiscounted_amount'] - $itemData['discount'];

                $quoteAmount += $itemData['amount'];

                $items[] = $itemData;
            }

            $data['amount'] = $quoteAmount;
            $data['tax_amount'] = $data['is_taxable'] ? $quoteAmount * 0.2 : 0;
            $data['final_amount'] = $quoteAmount + $data['tax_amount'];
            $data['total_phrase'] = NumberHelper::convertNumberToWords($data['final_amount']);
            $data['status'] = 'Brouillon';
            $authUser = Auth::user();
            $data['user_id'] = $authUser->id;
            $data['validity_date'] = \Carbon\Carbon::now()->addDays(15)->toDateString();

            $data = array_map(function ($value) {
                // Only convert truly empty values to null
                if ($value === '' || (empty($value) && $value !== 0 && $value !== '0' && $value !== false)) {
                    return null;
                }
                return $value;
            }, $data);


            $quote = $this->quoteRepository->create($data);
            if (!isset($data['code'])) {
                $duplicateCodeExists = Quote::where('code', $quote->code)
                    ->where('id', '!=', $quote->id)
                    ->exists();

                if ($duplicateCodeExists) {
                    $this->delete($quote->id);
                    return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
                }
            }
            $quoteId = $quote->id;

            foreach ($items as $itemData) {
                $itemData['quote_id'] = $quoteId;
                $this->allItemRepository
                    ->setModel(QuoteItem::class)->create($itemData);
            }

            return response()->json(['quote' => $quote, 'items' => $items], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Échec de la création du devis.', 'error' => $e->getMessage()], 500);
        }
    }
    public function duplicate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quote_id' => 'required|exists:quotes,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()], 422);
            }

            $quoteId = $request->input('quote_id');

            $originalQuote = $this->quoteRepository->getQuotes()->with([
                'items' => function ($query) {
                    $query->select("*")->orderBy('order');
                }
            ])->find($quoteId);

            if (!$originalQuote) {
                return response()->json(['message' => 'Devis introuvable'], 404);
            }

            $quoteData = $originalQuote->toArray();

            unset($quoteData['id']);
            unset($quoteData['created_at']);
            unset($quoteData['updated_at']);
            unset($quoteData['code']);
            unset($quoteData['process_group_id']);

            $quoteData['validity_date'] = Carbon::now()->addDays(15)->toDateString();

            $quoteData['status'] = 'Brouillon';

            $authUser = Auth::user();
            $quoteData['user_id'] = $authUser->id;

            $items = $quoteData['items'];
            unset($quoteData['items']);

            $newQuote = $this->quoteRepository->create($quoteData);
            if ($this->quoteRepository->codeExists($newQuote->code, $newQuote->id)) {
                $this->delete($newQuote->id);
                return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
            }
            $newQuoteId = $newQuote->id;

            $newItems = [];
            foreach ($items as $itemData) {

                unset($itemData['id']);
                unset($itemData['created_at']);
                unset($itemData['updated_at']);

                $itemData['quote_id'] = $newQuoteId;

                $newItem = $this->allItemRepository
                    ->setModel(QuoteItem::class)
                    ->create($itemData);

                $newItems[] = $newItem;
            }

            return response()->json([
                'message' => 'Devis dupliqué avec succès',
                'id' => $newQuote->id,
                'items' => $newItems
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Échec de la duplication du devis',
                'error' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }






    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string',
            ]);

            $quote = $this->quoteRepository->findById($id);

            if (!$quote) {
                return response()->json(['message' => 'Devis introuvable'], 404);
            }
            if ($quote->status === 'Terminé') {
                return response()->json(['message' => 'Ce devis ne peut pas être modifié'], 403);
            }

            $updated = $this->quoteRepository->update($id, ['status' => $request->input('status')]);

            if (!$updated) {
                return response()->json(['message' => 'Échec de la mise à jour du statut.'], 500);
            }

            $updatedQuote = $this->quoteRepository->findById($id);

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
                'message' => 'Une erreur est survenue lors de la mise à jour du statut',
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
            $quoteAmount = 0;

            $quote = $this->quoteRepository->findById($id);

            if (!$quote) {
                return response()->json(['message' => 'Devis introuvable.'], 404);
            }
            if ($quote->status === 'Validé' || $quote->status === 'Annulé' || $quote->status === 'Retourné' || $quote->status === 'Terminé') {
                return response()->json(['message' => 'Ce devis ne peut pas être modifié'], 403);
            }
            if (isset($data['code']) && $data['code'] === $quote->code) {
                unset($data['code']);
            } else if (isset($data['code'])) {
                $validator = Validator::make($data, [
                    'code' => 'unique:quotes,code,' . $id,
                ]);

                if ($validator->fails()) {
                    return response()->json(['message' => 'Ce code est déjà utilisé par un autre document.'], 422);
                }
            }

            $minValidity = Carbon::parse($quote->created_at)->addDays(15)->toDateString();
            if (empty($data['validity_date'])) {
                $data['validity_date'] = $minValidity;
            } elseif (Carbon::parse($data['validity_date'])->lt($minValidity)) {
                return response()->json([
                    'message' => "La date de validité doit être au moins $minValidity."
                ], 400);
            }

            $existingItems = $quote->items;

            $requestItemIds = isset($data['items']) ? array_column($data['items'], 'id') : [];

            $items = [];

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (isset($itemData['delete']) && $itemData['delete'] === true) {
                        if (isset($itemData['id'])) {
                            $this->allItemRepository
                                ->setModel(QuoteItem::class)->delete($itemData['id']);
                        }
                        continue;
                    }
                    $itemData['discount'] = isset($itemData['discount']) && is_numeric($itemData['discount'])
                        ? floatval($itemData['discount'])
                        : 0;

                    $itemData['undiscounted_amount'] = $itemData['price'] * $itemData['quantity'];
                    $itemData['amount'] = $itemData['undiscounted_amount'] - $itemData['discount'];

                    $quoteAmount += $itemData['amount'];

                    if (isset($itemData['id']) && $itemData['id'] !== null && $itemData['id'] !== '') {
                        $this->allItemRepository
                            ->setModel(QuoteItem::class)->update($itemData['id'], $itemData);
                    } else {
                        $itemData['quote_id'] = $id;
                        $this->allItemRepository
                            ->setModel(QuoteItem::class)->create($itemData);
                    }

                    $items[] = $itemData;
                }
            }

            $itemsToDelete = array_diff(array_column($existingItems->toArray(), 'id'), $requestItemIds);

            foreach ($itemsToDelete as $itemId) {
                $this->allItemRepository
                    ->setModel(QuoteItem::class)->delete($itemId);
            }

            $data['amount'] = $quoteAmount;
            $data['tax_amount'] = $data['is_taxable'] ? $quoteAmount * 0.2 : 0;
            $data['final_amount'] = $quoteAmount + $data['tax_amount'];
            $data['total_phrase'] = NumberHelper::convertNumberToWords($data['final_amount']);
            $data = array_map(function ($value) {
                // Only convert truly empty values to null
                if ($value === '' || (empty($value) && $value !== 0 && $value !== '0' && $value !== false)) {
                    return null;
                }
                return $value;
            }, $data);

            $quote = $this->quoteRepository->update($id, $data);

            return response()->json(['quote' => $quote, 'items' => $items]);
        } catch (\Exception $e) {
            return response()->json(['message' => "Une erreur s'est produite lors de la mise à jour du devis.", 'error' => $e->getMessage()], 500);
        }
    }



    public function delete($id)
    {
        try {
            $quote = $this->quoteRepository->findById($id);

            if (!$quote) {
                return response()->json(['message' => 'Devis introuvable'], 404);
            }

            foreach ($quote->items as $item) {
                $this->itemLogger->logDelete($item, $item->toArray());
                $item->delete();
            }

            $deleted = $this->quoteRepository->delete($id);

            if ($deleted) {
                return response()->json(['message' => 'Devis et éléments associés supprimés avec succès.']);
            }

            return response()->json(['message' => 'Échec de la suppression du devis.'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Une erreur est survenue lors de la suppression du devis.', 'error' => $e->getMessage()], 500);
        }
    }

    public function exportQuotes(
        Request $request,
        QuoteRepositoryInterface $quoteRepository,
        DocumentItemRepositoryInterface $allItemRepository
    ) {
        try {
            $filters = [
                'user_id' => $request->input('user'),
                'client_id' => $request->input('client'),
                'created_at' => $request->input('date'),
                'search' => $request->input('search'),
                'status' => $request->input('status'),
            ];

            $perPage = $request->input('per_page', 10);
            $fileName = 'quotes_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
            $filePath = 'public/' . $fileName;

            $allItemRepository->setModel(QuoteItem::class);



            $export = new QuoteExport($quoteRepository, $allItemRepository, $filters, $perPage);

            Excel::store($export, $filePath);

            $downloadUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'Devis exportés avec succès.',
                'download_url' => $downloadUrl,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Échec de l\'exportation des devis.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function generatePdf($id)
    {
        try {

            $quote = $this->quoteRepository
                ->getQuotes()
                ->with(['client', 'user', 'items'])
                ->find($id);

            if (!$quote) {
                return response()->json(['message' => 'Devis introuvable'], 404);
            }

            $quoteData = [
                'customer_name' => $quote->client->legal_name ?? 'N/A',
                'customer_address' => $quote->client->address ?? 'N/A',
                'total_in_words' => $quote->total_phrase,
                'comment' => $quote->delivery_comment,
                'total_ht' => $quote->amount,
                'discount' => $quote->discount ?? 0,
                'tva' => $quote->tax_amount,
                'total_ttc' => $quote->final_amount,
                'validity_date' => $quote->validity_date ?? 'N/A',
                'items' => $quote->items->map(function ($item) {
                    return [
                        'designation' => $item->description,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'total' => $item->amount,
                    ];
                })->toArray(),
            ];

            $pdf = Pdf::loadView('pdf.quote', ['quote' => $quoteData]);

            $fileName = 'quote_' . $quote->id . '_' . time() . '.pdf';


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


    public function generateDocument($id)
    {
        try {
            $quote = $this->quoteRepository->getQuotes()->with(['client', 'user', 'items'])->find($id);

            if (!$quote) {
                return response()->json(['message' => 'Devis introuvable'], 404);
            }
            if ($quote->status === 'Terminé') {
                return response()->json(['message' => 'Devis Terminé '], 404);
            }

            if ($quote->status !== 'Validé') {
                return response()->json(['message' => 'Le devis n\'est pas validé et ne peut pas être traité'], 400);
            }

            $data = [
                'quote_id' => $quote->id,
                'amount' => $quote->amount,
                'tax_amount' => $quote->tax_amount,
                'final_amount' => $quote->final_amount,
                'total_phrase' => $quote->total_phrase,
                'client_id' => $quote->client_id,
                'is_taxable' => $quote->is_taxable,
                'status' => 'Brouillon',
                'order_comment' => $quote->quote_comment,
            ];

            $authUser = Auth::user();
            $data['user_id'] = $authUser ? $authUser->id : null;

            if ($quote->items->isEmpty()) {
                return response()->json(['message' => 'Aucun élément dans le devis, impossible de créer le document.'], 400);
            }

            $orderNote = $this->orderNoteRepository->create($data);
            if ($this->orderNoteRepository->codeExists($orderNote->code, $orderNote->id)) {
                $this->orderNoteRepository->delete($orderNote->id);
                return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
            }

            $items = $quote->items->map(function ($item) use ($orderNote) {
                return [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'discount' => $item->discount,
                    'undiscounted_amount' => $item->undiscounted_amount,
                    'amount' => $item->amount,
                    'order_note_id' => $orderNote->id,
                    'order' => $item->order,
                ];
            });

            foreach ($items as $itemData) {
                $this->allItemRepository
                    ->setModel(OrderNoteItem::class)
                    ->create($itemData);
            }
            $this->quoteRepository->update($id, ["status" => "Terminé"]);

            return response()->json(['order_note' => $orderNote, 'items' => $items], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    // private function generateUniqueCode($prefix, $yearSuffix)
    // {

    //     $newCounter = 1;
    //     $latestEntry = $this->orderNoteRepository->getLatestByPrefix($prefix, $yearSuffix);

    //     if ($latestEntry && isset($latestEntry->code)) {
    //         preg_match("/{$prefix}-{$yearSuffix}-(\d+)/", $latestEntry->code, $matches);

    //         if (isset($matches[1])) {
    //             $latestCounter = (int)$matches[1];
    //             $newCounter = $latestCounter + 1;
    //         }
    //     }

    //     $code = "{$prefix}-{$yearSuffix}-" . str_pad($newCounter, 3, '0', STR_PAD_LEFT);

    //     if ($this->orderNoteRepository->codeExists($code)) {

    //         return $this->generateUniqueCode($prefix, $yearSuffix);
    //     }

    //     return $code;
    // }
    public function generateDocumentNavigation(Request $request)
    {
        try {
            $whereIamGoing = $request->input('where_iam_going');
            $quoteId = $request->input('quote_id');
            $processGroupId = $request->input('process_group_id');

            $models = [
                'Demande de devis' => QuoteRequest::class,
                'Devis' => Quote::class,
                'Bon de commande' => OrderNote::class,
                'Bon de production' => ProductionNote::class,
                'Bon de sortie' => OutputNote::class,
                'Bon de livraison' => DeliveryNote::class,
                'Bon de retour' => ReturnNote::class,
                'Facture' => Invoice::class,
                'Reçu de commande' => OrderReceipt::class,
                'Facture avoir' => InvoiceCredit::class,
                'Bon de remboursement' => RefundNote::class,
            ];

            if (!array_key_exists($whereIamGoing, $models)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Type de document invalide.'
                ], 400);
            }

            $modelClass = $models[$whereIamGoing];
            $document = null;

            // Determine which key to use based on priority
            $useProcessGroup = !empty($processGroupId);
            $useQuote = !empty($quoteId);

            // Special handling for 'Devis' ONLY when process_group_id is null/empty
            if ($whereIamGoing === 'Devis' && !$useProcessGroup && $useQuote) {
                $document = $modelClass::where('id', $quoteId)->first();
            }
            // Special handling for 'Demande de devis' - it only links to quote_id
            // elseif ($whereIamGoing === 'Demande de devis') {
            //     if ($useQuote) {
            //         $document = $modelClass::where('quote_id', $quoteId)->first();
            //     }
            // }
            // For all other documents (including Devis when process_group_id exists)
            else {
                // Priority logic: process_group_id > quote_id
                if ($useProcessGroup) {
                    $document = $modelClass::where('process_group_id', $processGroupId)->first();
                } elseif ($useQuote) {
                    $document = $modelClass::where('quote_id', $quoteId)->first();
                }
            }

            if (!$document) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Document introuvable'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'documentId' => $document->id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
