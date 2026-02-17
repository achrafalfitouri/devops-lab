<?php

namespace App\Http\Controllers;

use App\Exports\QuoteRequestExport;
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
use App\Models\QuoteRequestItem;
use App\Models\RefundNote;
use App\Models\ReturnNote;
use App\Repositories\Contracts\DocumentItemRepositoryInterface;
use App\Repositories\Contracts\OrderNoteRepositoryInterface;
use App\Repositories\Contracts\QuoteRepositoryInterface;
use App\Repositories\Contracts\QuoteRequestRepositoryInterface;
use App\Services\ItemLogger;
use App\Traits\HandlesConditionalRelationships;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;


class QuoteRequestController extends Controller
{
    use HandlesConditionalRelationships;

    protected $quoterequestRepository;
    protected $quoteRepository;
    protected $orderNoteItemRepository;
    protected $allItemRepository;
    protected $itemLogger;


    public function __construct(
        QuoteRequestRepositoryInterface $quoterequestRepository,
        QuoteRepositoryInterface $quoteRepository,
        DocumentItemRepositoryInterface $allItemRepository
    ) {
        $this->quoterequestRepository = $quoterequestRepository;
        $this->quoteRepository = $quoteRepository;
        $this->allItemRepository = $allItemRepository;
        $this->itemLogger = new ItemLogger();
    }


    public function getQuoteRequest(Request $request, QuoteRequestRepositoryInterface $repository)
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
                $query = $repository->getQuoteRequests()->with([
                    'client:id,legal_name',
                    'user:id,full_name',
                    'items',
                ])->onlyTrashed();
            } else {
                $query = $repository->getQuoteRequests()->with([
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
            $quoterequests = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'current_page' => $quoterequests->currentPage(),
                'total_quoterequests' => $quoterequests->total(),
                'per_page' => $quoterequests->perPage(),
                'quoterequests' => $quoterequests

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
                    $query->select('id', 'status', 'code', 'quote_request_id', "is_taxable");
                },
                'quoterequests' => function ($query) {
                    $query->select('id', 'quote_id', 'client_id',  'status', 'amount', 'code', "tax_amount", "is_taxable", 'final_amount')
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
                $quoterequest = $this->quoterequestRepository->getQuoteRequests()->onlyTrashed()->with($relationships)->find($id);
            } else {
                $quoterequest = $this->quoterequestRepository->getQuoteRequests()->with($relationships)->find($id);
            }

            if (!$quoterequest) {
                return response()->json(['message' => 'Demande de devis introuvable'], 404);
            }

            $quoterequest = $this->mergeConditionalRelationships($quoterequest);


            return response()->json($quoterequest);
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
                'amount' => 'nullable|numeric|min:0',
                'is_taxable' => 'required|boolean',
                'tax_amount' => 'nullable|numeric|min:0',
                'final_amount' => 'nullable|numeric|min:0',
                'total_phrase' => 'nullable|string|max:255',
                'quoterequest_comment' => 'nullable|string|max:500',
                'status' => 'nullable|string|in:Brouillon,Validé,Annulé',
                'client_id' => 'required|exists:clients,id',
                'user_id' => 'nullable|exists:users,id',

                'items' => 'required|array|min:1',
                'items.*.description' => 'nullable|string|max:255',
                'items.*.characteristics' => 'nullable|string|max:255',
                'items.*.price' => 'nullable|numeric|min:0',
                'items.*.quantity' => 'nullable|numeric|min:0',
                'items.*.undiscounted_amount' => 'nullable|numeric|min:0',
                'items.*.discount' => 'nullable|numeric|min:0',
                'items.*.amount' => 'nullable|numeric|min:0',
                'items.*.order' => 'nullable|integer',
                'items.*.status' => 'nullable|string|in:Validé,Brouillon,Annulé',
            ], [
                'required' => 'champ est obligatoire.',
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
                return response()->json(['message' => 'Impossible de créer une demande devis pour ce client.'], 403);
            }

            $quoterequestAmount = 0;
            $items = [];

            foreach ($data['items'] as $itemData) {
                $rawPrice = $itemData['price'] ?? null;
                $rawQuantity = $itemData['quantity'] ?? null;

                $price = is_numeric($rawPrice) ? floatval($rawPrice) : 0.0;
                $quantity = is_numeric($rawQuantity) ? floatval($rawQuantity) : 0.0;

                $itemData['discount'] = isset($itemData['discount']) && is_numeric($itemData['discount'])
                    ? floatval($itemData['discount'])
                    : 0.0;

                $itemData['price'] = is_numeric($rawPrice) ? round($price, 2) : null;
                $itemData['quantity'] = is_numeric($rawQuantity) ? (int) $quantity : null;

                $itemData['undiscounted_amount'] = $price * $quantity;
                $computedAmount = $itemData['undiscounted_amount'] - $itemData['discount'];
                $itemData['amount'] = $computedAmount > 0 ? $computedAmount : 0.0;

                $quoterequestAmount += $itemData['amount'];

                $items[] = $itemData;
            }

            $data['amount'] = $quoterequestAmount;
            $data['tax_amount'] = $data['is_taxable'] ? $quoterequestAmount * 0.2 : 0;
            $data['final_amount'] = $quoterequestAmount + $data['tax_amount'];
            $data['total_phrase'] = NumberHelper::convertNumberToWords($data['final_amount']);
            $data['status'] = 'Brouillon';
            $authUser = Auth::user();
            $data['user_id'] = $authUser->id;

            $data = array_map(function ($value) {
                // Only convert truly empty values to null
                if ($value === '' || (empty($value) && $value !== 0 && $value !== '0' && $value !== false)) {
                    return null;
                }
                return $value;
            }, $data);


            $quoterequest = $this->quoterequestRepository->create($data);
            if (!isset($data['code'])) {
                $duplicateCodeExists = QuoteRequest::where('code', $quoterequest->code)
                    ->where('id', '!=', $quoterequest->id)
                    ->exists();

                if ($duplicateCodeExists) {
                    $this->delete($quoterequest->id);
                    return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
                }
            }
            $quoterequestId = $quoterequest->id;

            foreach ($items as $itemData) {
                $itemData['quote_request_id'] = $quoterequestId;
                $this->allItemRepository
                    ->setModel(QuoteRequestItem::class)->create($itemData);
            }

            return response()->json(['quoterequest' => $quoterequest, 'items' => $items], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Échec de la création du demande de devis.', 'error' => $e->getMessage()], 500);
        }
    }
    public function duplicate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quote_request_id' => 'required|exists:quote_requests,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()], 422);
            }

            $quoterequestId = $request->input('quote_request_id');

            $originalQuote = $this->quoterequestRepository->getQuoteRequests()->with([
                'items' => function ($query) {
                    $query->select("*")->orderBy('order');
                }
            ])->find($quoterequestId);

            if (!$originalQuote) {
                return response()->json(['message' => 'Demande de devis'], 404);
            }

            $quoterequestData = $originalQuote->toArray();

            unset($quoterequestData['id']);
            unset($quoterequestData['created_at']);
            unset($quoterequestData['updated_at']);
            unset($quoterequestData['code']);
            unset($quoterequestData['process_group_id']);
            unset($quoterequestData['quote_id']);

            $quoterequestData['status'] = 'Brouillon';

            $authUser = Auth::user();
            $quoterequestData['user_id'] = $authUser->id;

            $items = $quoterequestData['items'];
            unset($quoterequestData['items']);

            $newQuote = $this->quoterequestRepository->create($quoterequestData);
            if ($this->quoterequestRepository->codeExists($newQuote->code, $newQuote->id)) {
                $this->delete($newQuote->id);
                return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
            }
            $newQuoteId = $newQuote->id;

            $newItems = [];
            foreach ($items as $itemData) {

                unset($itemData['id']);
                unset($itemData['created_at']);
                unset($itemData['updated_at']);

                $itemData['quote_request_id'] = $newQuoteId;

                $newItem = $this->allItemRepository
                    ->setModel(QuoteRequestItem::class)
                    ->create($itemData);

                $newItems[] = $newItem;
            }

            return response()->json([
                'message' => 'Demande de devis dupliqué avec succès',
                'id' => $newQuote->id,
                'items' => $newItems
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Échec de la duplication de la demande de devis',
                'error' => $e->getMessage()
            ], is_int($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }


    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string',
            ]);

            $quoterequest = $this->quoterequestRepository->findById($id);

            if (!$quoterequest) {
                return response()->json(['message' => 'Demande de devis'], 404);
            }
            if ($quoterequest->status === 'Terminé') {
                return response()->json(['message' => 'Cette demande de devis ne peut pas être modifié'], 403);
            }

            $updated = $this->quoterequestRepository->update($id, ['status' => $request->input('status')]);

            if (!$updated) {
                return response()->json(['message' => 'Échec de la mise à jour du statut.'], 500);
            }

            $updatedQuote = $this->quoterequestRepository->findById($id);

            $responseQuote = [
                'id' => $updatedQuote->id,
                'status' => $updatedQuote->status,
            ];

            return response()->json([
                'message' => 'Statut mis à jour avec succès.',
                'quoterequest' => $responseQuote,
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
            $quoterequestAmount = 0;

            $quoterequest = $this->quoterequestRepository->findById($id);

            if (!$quoterequest) {
                return response()->json(['message' => 'Demande de devis.'], 404);
            }
            if ($quoterequest->status === 'Validé' || $quoterequest->status === 'Annulé' || $quoterequest->status === 'Retourné' || $quoterequest->status === 'Terminé') {
                return response()->json(['message' => 'Cette demande de devis ne peut pas être modifié'], 403);
            }
            if (isset($data['code']) && $data['code'] === $quoterequest->code) {
                unset($data['code']);
            } else if (isset($data['code'])) {
                $validator = Validator::make($data, [
                    'code' => 'unique:quoterequests,code,' . $id,
                ]);

                if ($validator->fails()) {
                    return response()->json(['message' => 'Ce code est déjà utilisé par un autre document.'], 422);
                }
            }

            $existingItems = $quoterequest->items;

            $requestItemIds = isset($data['items']) ? array_column($data['items'], 'id') : [];

            $items = [];

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (isset($itemData['delete']) && $itemData['delete'] === true) {
                        if (isset($itemData['id'])) {
                            $this->allItemRepository
                                ->setModel(QuoteRequestItem::class)->delete($itemData['id']);
                        }
                        continue;
                    }

                    // Convertir price et quantity en valeurs numériques (0 si null ou vide)
                    $itemData['price'] = isset($itemData['price']) && is_numeric($itemData['price'])
                        ? floatval($itemData['price'])
                        : 0;

                    $itemData['quantity'] = isset($itemData['quantity']) && is_numeric($itemData['quantity'])
                        ? floatval($itemData['quantity'])
                        : 0;

                    $itemData['discount'] = isset($itemData['discount']) && is_numeric($itemData['discount'])
                        ? floatval($itemData['discount'])
                        : 0;

                    $itemData['undiscounted_amount'] = $itemData['price'] * $itemData['quantity'];
                    $itemData['amount'] = $itemData['undiscounted_amount'] - $itemData['discount'];

                    $quoterequestAmount += $itemData['amount'];

                    if (isset($itemData['id']) && $itemData['id'] !== null && $itemData['id'] !== '') {
                        $this->allItemRepository
                            ->setModel(QuoteRequestItem::class)->update($itemData['id'], $itemData);
                    } else {
                        $itemData['quote_request_id'] = $id;
                        $this->allItemRepository
                            ->setModel(QuoteRequestItem::class)->create($itemData);
                    }

                    $items[] = $itemData;
                }
            }

            $itemsToDelete = array_diff(array_column($existingItems->toArray(), 'id'), $requestItemIds);

            foreach ($itemsToDelete as $itemId) {
                $this->allItemRepository
                    ->setModel(QuoteRequestItem::class)->delete($itemId);
            }

            $data['amount'] = $quoterequestAmount;
            $data['tax_amount'] = $data['is_taxable'] ? $quoterequestAmount * 0.2 : 0;
            $data['final_amount'] = $quoterequestAmount + $data['tax_amount'];
            $data['total_phrase'] = NumberHelper::convertNumberToWords($data['final_amount']);
            $data = array_map(function ($value) {
                // Only convert truly empty values to null
                if ($value === '' || (empty($value) && $value !== 0 && $value !== '0' && $value !== false)) {
                    return null;
                }
                return $value;
            }, $data);

            $quoterequest = $this->quoterequestRepository->update($id, $data);

            return response()->json(['quoterequest' => $quoterequest, 'items' => $items]);
        } catch (\Exception $e) {
            return response()->json(['message' => "Une erreur s'est produite lors de la mise à jour de la demande de devis.", 'error' => $e->getMessage()], 500);
        }
    }


    public function delete($id)
    {
        try {
            $quoterequest = $this->quoterequestRepository->findById($id);

            if (!$quoterequest) {
                return response()->json(['message' => 'Demande de devis'], 404);
            }

            foreach ($quoterequest->items as $item) {
                $this->itemLogger->logDelete($item, $item->toArray());
                $item->delete();
            }

            $deleted = $this->quoterequestRepository->delete($id);

            if ($deleted) {
                return response()->json(['message' => 'Devis et éléments associés supprimés avec succès.']);
            }

            return response()->json(['message' => 'Échec de la suppression de la demande de devis.'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Une erreur est survenue lors de la suppression de demande de devis.', 'error' => $e->getMessage()], 500);
        }
    }

    public function exportQuotes(
        Request $request,
        QuoteRequestRepositoryInterface $quoterequestRepository,
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
            $fileName = 'quoterequests_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
            $filePath = 'public/' . $fileName;

            $allItemRepository->setModel(QuoteRequestItem::class);



            $export = new QuoteRequestExport($quoterequestRepository, $allItemRepository, $filters, $perPage);

            Excel::store($export, $filePath);

            $downloadUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'Demande de devis exportés avec succès.',
                'download_url' => $downloadUrl,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Échec de l\'exportation des demandes de devis.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function generatePdf($id)
    {
        try {

            $quoterequest = $this->quoterequestRepository
                ->getQuoteRequests()
                ->with(['client', 'user', 'items'])
                ->find($id);

            if (!$quoterequest) {
                return response()->json(['message' => 'Demande de devis'], 404);
            }

            $quoterequestData = [
                'customer_name' => $quoterequest->client->legal_name ?? 'N/A',
                'customer_address' => $quoterequest->client->address ?? 'N/A',
                'total_in_words' => $quoterequest->total_phrase,
                'comment' => $quoterequest->delivery_comment,
                'total_ht' => $quoterequest->amount,
                'discount' => $quoterequest->discount ?? 0,
                'tva' => $quoterequest->tax_amount,
                'total_ttc' => $quoterequest->final_amount,
                'items' => $quoterequest->items->map(function ($item) {
                    return [
                        'designation' => $item->description,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'total' => $item->amount,
                    ];
                })->toArray(),
            ];

            $pdf = Pdf::loadView('pdf.quoterequest', ['quoterequest' => $quoterequestData]);

            $fileName = 'quoterequest_' . $quoterequest->id . '_' . time() . '.pdf';


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
            $quoterequest = $this->quoterequestRepository->getQuoteRequests()->with(['client', 'user', 'items'])->find($id);

            if (!$quoterequest) {
                return response()->json(['message' => 'Demande de devis introuvable'], 404);
            }
            if ($quoterequest->status === 'Terminé') {
                return response()->json(['message' => 'Demande de devis Terminé '], 404);
            }

            if ($quoterequest->status !== 'Validé') {
                return response()->json(['message' => 'La demande de devis n\'est pas validé et ne peut pas être traité'], 400);
            }

            $data = [
                'quote_request_id' => $quoterequest->id,
                'amount' => $quoterequest->amount,
                'tax_amount' => $quoterequest->tax_amount,
                'final_amount' => $quoterequest->final_amount,
                'total_phrase' => $quoterequest->total_phrase,
                'client_id' => $quoterequest->client_id,
                'is_taxable' => $quoterequest->is_taxable,
                'status' => 'Brouillon',
                'quote_comment' => $quoterequest->quoterequest_comment,
            ];

            $authUser = Auth::user();
            $data['user_id'] = $authUser ? $authUser->id : null;
            $data['validity_date'] = \Carbon\Carbon::now()->addDays(15)->toDateString();

            if ($quoterequest->items->isEmpty()) {
                return response()->json(['message' => 'Aucun élément dans la demande de devis, impossible de créer le document.'], 400);
            }

            // Validate all items have price and quantity filled and > 0
            $invalidItems = $quoterequest->items->filter(function ($item) {
                $price = $item->price;
                $quantity = $item->quantity;
                $priceInvalid = $price === null || $price === '' || !is_numeric($price) || floatval($price) <= 0;
                $quantityInvalid = $quantity === null || $quantity === '' || !is_numeric($quantity) || floatval($quantity) <= 0;
                return $priceInvalid || $quantityInvalid;
            });

            if ($invalidItems->isNotEmpty()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Tous les articles doivent avoir un prix et une quantité renseignés et supérieurs à 0.'
                ], 422);
            }

            $quote = $this->quoteRepository->create($data);
            if ($this->quoteRepository->codeExists($quote->code, $quote->id)) {
                $this->quoteRepository->delete($quote->id);
                return response()->json(['message' => 'Un code identique existe déjà dans le système'], 409);
            }

            // Link created quote to the originating quote request
            $this->quoterequestRepository->update($id, ['quote_id' => $quote->id]);

            $items = $quoterequest->items->map(function ($item) use ($quote) {
                return [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'discount' => $item->discount,
                    'undiscounted_amount' => $item->undiscounted_amount,
                    'amount' => $item->amount,
                    'quote_id' => $quote->id,
                    'order' => $item->order,
                ];
            });

            foreach ($items as $itemData) {
                $this->allItemRepository
                    ->setModel(QuoteItem::class)
                    ->create($itemData);
            }
            $this->quoterequestRepository->update($id, ["status" => "Terminé"]);

            return response()->json(['quote' => $quote, 'items' => $items], 201);
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
    //     $latestEntry = $this->quoteRepository->getLatestByPrefix($prefix, $yearSuffix);

    //     if ($latestEntry && isset($latestEntry->code)) {
    //         preg_match("/{$prefix}-{$yearSuffix}-(\d+)/", $latestEntry->code, $matches);

    //         if (isset($matches[1])) {
    //             $latestCounter = (int)$matches[1];
    //             $newCounter = $latestCounter + 1;
    //         }
    //     }

    //     $code = "{$prefix}-{$yearSuffix}-" . str_pad($newCounter, 3, '0', STR_PAD_LEFT);

    //     if ($this->quoteRepository->codeExists($code)) {

    //         return $this->generateUniqueCode($prefix, $yearSuffix);
    //     }

    //     return $code;
    // }
    public function generateDocumentNavigation(Request $request)
    {
        try {
            $whereIamGoing = $request->input('where_iam_going');
            $quoterequestId = $request->input('quote_id');

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

            if ($whereIamGoing === 'Devis') {
                $document = $modelClass::where('id', $quoterequestId)->first();
            } else {
                $document = $modelClass::where('quote_id', $quoterequestId)->first();
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
