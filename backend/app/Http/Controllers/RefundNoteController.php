<?php

namespace App\Http\Controllers;

use App\Exports\RefundNoteExport;
use App\Helpers\FilterHelper;
use App\Helpers\NumberHelper;
use App\Models\RefundItem;
use App\Repositories\Contracts\DocumentItemRepositoryInterface;
use App\Repositories\Contracts\RefundNoteRepositoryInterface;
use App\Traits\HandlesConditionalRelationships;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class RefundNoteController extends Controller
{
    use HandlesConditionalRelationships;
    protected $refundNoteRepository;
    protected $documentItemRepository;


    public function __construct(RefundNoteRepositoryInterface $refundNoteRepository, DocumentItemRepositoryInterface $documentItemRepository)

    {
        $this->refundNoteRepository = $refundNoteRepository;
        $this->documentItemRepository = $documentItemRepository;
    }


    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string',
            ]);

            $refundNote = $this->refundNoteRepository->get($id);

            if (!$refundNote) {
                $errors[] = ['id' => $id, 'error' => 'Refund note not found'];
                return response()->json(['errors' => $errors], 404);
            }

            if ($refundNote->status === 'Soldé') {
                $errors[] = ['id' => $id, 'error' => 'Déjà finalisé et ne peut pas être modifié'];
                return response()->json(['errors' => $errors], 400);
            }
            $updated = $this->refundNoteRepository->update($id, ['status' => $request->input('status')]);

            if (!$updated) {
                return response()->json(['message' => 'Échec de la mise à jour du statut.'], 500);
            }

            $updatedQuote = $this->refundNoteRepository->get($id);

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

    public function updateType(Request $request, $id)
    {
        try {
            $request->validate([
                'type' => 'required|string',
            ]);



            $updated = $this->refundNoteRepository->update($id, ['payment_type_id' => $request->input('type')]);

            if (!$updated) {
                return response()->json(['message' => 'Échec de la mise à jour du type.'], 500);
            }

            $refund = $this->refundNoteRepository->get($id);

            $responseQuote = [
                'id' => $refund->id,
                'type' => $refund->status,
            ];

            return response()->json([
                'message' => 'Statut mis à jour avec succès.',
                'quote' => $responseQuote,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour du type',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getById(Request $request, $id)
    {
        try {
            $isArchived = filter_var($request->query('archive'), FILTER_VALIDATE_BOOLEAN);

            $baseRelationships = [
                'client:id,legal_name,ice',
                'user:id,code,full_name',
                'paymentType:id,name',
                'items' => function ($query) {
                    $query->select("*")->orderBy('order');
                },
                // 'orderReceipts' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                // },
                // 'refunds' => function ($query) {
                //     $query->select('id','client_id',  'status', 'code', 'tax_amount', 'quote_id', 'is_taxable', 'amount', 'final_amount')
                //         ->with([
                //             'client:id,legal_name,ice,balance',
                //             'items' => function ($subQuery) {
                //             $subQuery->select('*')->orderBy('order');
                //         }])
                //         ->orderBy('code');
                // },
                'quotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'is_taxable');
                },
                'quoterequests' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', "is_taxable");
                },
                'deliveryNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                },
                // 'returnNotes' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                // },
                // 'invoices' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                // },
                'orderNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                },
                'outputNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                },
                // 'invoiceCredits' => function ($query) {
                //     $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
                // },
                'productionNotes' => function ($query) {
                    $query->select('id', 'status', 'code', 'quote_id', 'is_taxable');
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
                    $query->select('id','client_id',  'status', 'code', 'tax_amount', 'quote_id', 'is_taxable', 'amount', 'final_amount','process_group_id')
                        ->with([
                            'client:id,legal_name,ice,balance',
                            'items' => function ($subQuery) {
                            $subQuery->select('*')->orderBy('order');
                        }])
                        ->orderBy('code');
                },
            ];
            // Merge relationships (this automatically handles the dual loading)
            $relationships = $this->buildRelationshipsWithConditional(
                $baseRelationships,
                $conditionalRelationships
            );

            if ($isArchived) {
                $refund = $this->refundNoteRepository->getAll()->onlyTrashed()->with($relationships)->find($id);
            } else {
                $refund = $this->refundNoteRepository->getAll()->with($relationships)->find($id);
            }

            if (!$refund) {
                return response()->json(['message' => 'Remboursement introuvable'], 404);
            }
            $refund = $this->mergeConditionalRelationships($refund);

            return response()->json($refund);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }


    public function getAll(Request $request, RefundNoteRepositoryInterface $repository)
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
            $perPage = $request->input('per_page', 10);
            if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
                $query = $repository->getAll()->with([
                    'client:id,legal_name',
                    'user:id,full_name',
                    'items'
                ])->onlyTrashed();
            } else {
                $query = $repository->getAll()->with(['client:id,legal_name,ice', 'user:id,full_name', 'items']);
            }
            $filterableFields = ['user_id', 'client_id', 'created_at', 'quote_id', 'status'];
            $searchableFields = ['code', 'total_phrase'];

            $query = FilterHelper::applyFilters($query, $filters, $filterableFields);

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $query = FilterHelper::applySearch($query, $filters['search'], $searchableFields);

            $query->orderBy('created_at', 'desc');
            $data = $query->paginate($perPage);

            return response()->json([
                'status' => 200,
                'current_page' => $data->currentPage(),
                'total_invoice_credits' => $data->total(),
                'per_page' => $data->perPage(),
                'refunds' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function exportRefundNotes(Request $request)
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

            $fileName = 'refund_notes_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
            $filePath = storage_path('app/public/' . $fileName);

            $export = new RefundNoteExport($filters, $perPage);

            Excel::store($export, 'public/' . $fileName);

            $downloadUrl = asset('storage/' . $fileName);

            return response()->json([
                'status' => 200,
                'message' => 'Notes de remboursement exportées avec succès.',
                'download_url' => $downloadUrl,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Échec de l\'exportation des notes de remboursement : ' . $e->getMessage(),
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
            $returnNoteAmount = 0;

            $returnNote = $this->refundNoteRepository->get($id);

            if (!$returnNote) {
                return response()->json(['message' => 'refund introuvable.'], 404);
            }

            if (isset($data['code']) && $data['code'] === $returnNote->code) {
                unset($data['code']);
            } else if (isset($data['code'])) {
                $validator = Validator::make($data, [
                    'code' => 'unique:refund_notes,code,' . $id,
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
                            ->setModel(RefundItem::class)->update($itemData['id'], $itemData);
                    } else {
                        $itemData['quote_id'] = $id;
                        $this->documentItemRepository
                            ->setModel(RefundItem::class)->create($itemData);
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


            $returnNote = $this->refundNoteRepository->update($id, $data);

            return response()->json([
                'status' => 200,
                'refundnote' => $returnNote,
                'items' => $items
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
    public function delete($id)
    {
        $doc = $this->refundNoteRepository->get($id);

        $this->refundNoteRepository->delete($id);

        return response()->json(['message' => ' document supprimé avec succès.'], 200);
    }
}
