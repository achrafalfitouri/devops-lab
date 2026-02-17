<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\StaticController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DeliveryNoteController;
use App\Http\Controllers\DetailController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceCreditController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\OrderNoteController;
use App\Http\Controllers\OrderReceiptController;
use App\Http\Controllers\OutputNoteController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductionNoteController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteRequestController;
use App\Http\Controllers\RefundNoteController;
use App\Http\Controllers\ReturnNoteController;
use App\Http\Controllers\TransactionController;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\XssSanitizer;

// Public routes
Route::post('/user/login', [UserController::class, 'login']);
Route::post('/user/logout', [UserController::class, 'logout']);
Route::post('/password/request-reset', [UserController::class, 'requestPasswordReset']);

Route::middleware(['auth:sanctum', XssSanitizer::class])->group(function () {
    // Client routes
    Route::prefix('client')->group(function () {
        Route::middleware(CheckPermission::class . ':view_client')->post('/', [ClientController::class, 'getClients']);
        Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [ClientController::class, 'getClients']);
        Route::middleware(CheckPermission::class . ':view_client')->get('/{id}', [ClientController::class, 'getClientById']);
        Route::middleware(CheckPermission::class . ':view_archive')->get('/{id}/archive', [ClientController::class, 'getClientById']);
        Route::middleware(CheckPermission::class . ':add_client')->post('/create', [ClientController::class, 'create']);
        Route::middleware(CheckPermission::class . ':edit_client')->put('/{id}', [ClientController::class, 'update']);
        Route::middleware(CheckPermission::class . ':delete_client')->delete('/{id}', [ClientController::class, 'softDelete']);
        Route::middleware(CheckPermission::class . ':edit_client')->post('/{id}/upload_logo', [ClientController::class, 'uploadLogo']);
        Route::middleware(CheckPermission::class . ':edit_client')->post('/{id}/update_logo', [ClientController::class, 'uploadLogo']);
        Route::middleware(CheckPermission::class . ':view_client')->post('/export', [ClientController::class, 'exportClients']);
        Route::middleware(CheckPermission::class . ':view_logs')->post('/clientlogs', [LogController::class, 'getClientLogs']);
        Route::middleware(CheckPermission::class . ':view_logs')->get('/clientlogs/{id}', [LogController::class, 'getClientLogById']);
    });

    // Export routes
    Route::group(['prefix' => 'export'], function () {
        Route::post('/client', [ClientController::class, 'exportClients']);
        Route::post('/transaction', [TransactionController::class, 'exportTransactions']);
        Route::post('/payement', [PaymentController::class, 'exportPayments']);
        Route::post('/quote', [QuoteController::class, 'exportQuotes']);
        Route::post('/quoterequest', [QuoteRequestController::class, 'exportQuotes']);
        Route::post('/ordernote', [OrderNoteController::class, 'exportOrderNotes']);
        Route::post('/productionnote', [ProductionNoteController::class, 'exportProductionNotes']);
        Route::post('/outputnote', [OutputNoteController::class, 'exportOutputNotes']);
        Route::post('/deliverynote', [DeliveryNoteController::class, 'exportDeliveryNotes']);
        Route::post('/returnenote', [ReturnNoteController::class, 'export']);
        Route::post('/invoice', [InvoiceController::class, 'exportInvoices']);
        Route::post('/orderreceipt', [OrderReceiptController::class, 'exportOrderReceipts']);
        Route::post('/invoicecredit', [InvoiceCreditController::class, 'exportInvoiceCredits']);
        Route::post('/user', [UserController::class, 'exportUsers']);
        Route::post('/refund', [RefundNoteController::class, 'exportRefundNotes']);
    });

    // User routes

    Route::prefix('user')->group(function () {
        Route::get('profil/{id}', [UserController::class, 'getUserById']);

        Route::middleware(CheckPermission::class . ':view_user')->post('/', [UserController::class, 'getUsers']);
        Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [UserController::class, 'getUsers']);

        Route::middleware(CheckPermission::class . ':view_archive')->get('/{id}/archive', [UserController::class, 'getUserById']);
        Route::middleware(CheckPermission::class . ':view_user')->get('/{id}', [UserController::class, 'getUserById']);

        Route::middleware(CheckPermission::class . ':add_user')->post('/create', [UserController::class, 'create']);
        Route::middleware(CheckPermission::class . ':edit_user')->put('/{id}', [UserController::class, 'update']);
        Route::middleware(CheckPermission::class . ':delete_user')->delete('/{id}', [UserController::class, 'delete']);
        Route::middleware([
            CheckPermission::class . ':add_user',
            CheckPermission::class . ':edit_user'
        ])->group(function () {
            Route::post('/{id}/upload_photo', [UserController::class, 'uploadPhoto']);
        });

        Route::middleware(CheckPermission::class . ':edit_user')->post('/{id}/update_photo', [UserController::class, 'uploadPhoto']);
        Route::get('/check/auth', [UserController::class, 'checkAuth']);
        Route::middleware(CheckPermission::class . ':assign_role')->post('/{userId}/assign-role', [UserController::class, 'assignRole']);
        Route::middleware(CheckPermission::class . ':assign_cashregister')->post('/{cashregisterID}/assign', [UserController::class, 'assignUser']);
        Route::middleware(CheckPermission::class . ':view_logs')->post('/userlogs', [LogController::class, 'getUserLogs']);
        Route::middleware(CheckPermission::class . ':view_logs')->get('/userlogs/{id}', [LogController::class, 'getUserLogById']);
    });

    // Cash Register routes
    Route::prefix('cash-registers')->group(function () {
        Route::middleware(CheckPermission::class . ':view_cashregister')->post('/', [CashRegisterController::class, 'getAll']);
        Route::middleware(CheckPermission::class . ':add_cashregister')->post('/create', [CashRegisterController::class, 'createCashRegister']);
        Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [CashRegisterController::class, 'getAll']);
        Route::middleware(CheckPermission::class . ':edit_cashregister')->put('/{id}', [CashRegisterController::class, 'updateCashRegister']);
        Route::middleware(CheckPermission::class . ':delete_cashregister')->delete('/{id}', [CashRegisterController::class, 'softDeleteCashRegister']);
        Route::middleware(CheckPermission::class . ':edit_cashregister')->put('/status/{id}', [CashRegisterController::class, 'updateStatus']);
    });

    // Transaction routes
    Route::prefix('transactions')->group(function () {
        Route::middleware(CheckPermission::class . ':view_transaction')->post('/', [TransactionController::class, 'getAll']);
        Route::middleware(CheckPermission::class . ':add_transaction')->post('/create', [TransactionController::class, 'createTransaction']);
        Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [TransactionController::class, 'getAll']);
        Route::middleware(CheckPermission::class . ':view_transaction')->get('/{id}', [TransactionController::class, 'getTransactionById']);
        Route::middleware(CheckPermission::class . ':edit_transaction')->put('/{id}', [TransactionController::class, 'updateTransaction']);
        Route::middleware(CheckPermission::class . ':delete_transaction')->delete('/{id}', [TransactionController::class, 'softDeleteTransaction']);
        Route::middleware(CheckPermission::class . ':view_logs')->post('/transactionlogs', [LogController::class, 'getTransactionLogs']);

        Route::middleware(CheckPermission::class . ':view_logs')->get('/transactionlogs/{id}', [LogController::class, 'getTransactionLogById']);
    });

    // QuoteRequest routes
    Route::prefix('quoterequests')->group(function () {
        Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [QuoteRequestController::class, 'getQuoteRequest']);
        Route::middleware(CheckPermission::class . ':view_document')->post('/', [QuoteRequestController::class, 'getQuoteRequest']);
        Route::middleware(CheckPermission::class . ':view_archive')->get('/{id}/archive', [QuoteRequestController::class, 'getQuoteById']);
        Route::middleware(CheckPermission::class . ':view_document')->get('/{id}', [QuoteRequestController::class, 'getQuoteById']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/create', [QuoteRequestController::class, 'create']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/{id}', [QuoteRequestController::class, 'update']);
        Route::middleware(CheckPermission::class . ':delete_document')->delete('/{id}', [QuoteRequestController::class, 'delete']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/status/{id}', [QuoteRequestController::class, 'updateStatus']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/duplicate', [QuoteRequestController::class, 'duplicate']);
        Route::post('/{id}/generate-document', [QuoteRequestController::class, 'generateDocument']);
    });
    // Quote routes
    Route::prefix('quotes')->group(function () {
        Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [QuoteController::class, 'getQuotes']);
        Route::middleware(CheckPermission::class . ':view_document')->post('/', [QuoteController::class, 'getQuotes']);
        Route::middleware(CheckPermission::class . ':view_archive')->get('/{id}/archive', [QuoteController::class, 'getQuoteById']);
        Route::middleware(CheckPermission::class . ':view_document')->get('/{id}', [QuoteController::class, 'getQuoteById']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/create', [QuoteController::class, 'create']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/{id}', [QuoteController::class, 'update']);
        Route::middleware(CheckPermission::class . ':delete_document')->delete('/{id}', [QuoteController::class, 'delete']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/status/{id}', [QuoteController::class, 'updateStatus']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/duplicate', [QuoteController::class, 'duplicate']);
        Route::post('/{id}/generate-document', [QuoteController::class, 'generateDocument']);
    });

    // Output Note routes
    Route::prefix('outputnotes')->group(function () {
        Route::middleware(CheckPermission::class . ':view_document')->post('/', [OutputNoteController::class, 'getOutputNotes']);
        Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [OutputNoteController::class, 'getOutputNotes']);
        Route::middleware(CheckPermission::class . ':view_document')->get('/{id}', [OutputNoteController::class, 'getOutputNoteById']);
        Route::middleware(CheckPermission::class . ':view_archive')->get('/{id}/archive', [OutputNoteController::class, 'getOutputNoteById']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/create', [OutputNoteController::class, 'create']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/{id}', [OutputNoteController::class, 'update']);
        Route::middleware(CheckPermission::class . ':delete_document')->delete('/{id}', [OutputNoteController::class, 'delete']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/status/{id}', [OutputNoteController::class, 'updateStatus']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/generate-document', [OutputNoteController::class, 'generateDocument']);
    });

    // Delivery Note routes
    Route::prefix('deliverynotes')->group(function () {
        Route::middleware(CheckPermission::class . ':view_document')->post('/', [DeliveryNoteController::class, 'getDeliveryNotes']);
        Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [DeliveryNoteController::class, 'getDeliveryNotes']);
        Route::middleware(CheckPermission::class . ':view_document')->get('/{id}', [DeliveryNoteController::class, 'getDeliveryNoteById']);
        Route::middleware(CheckPermission::class . ':view_archive')->get('/{id}/archive', [DeliveryNoteController::class, 'getDeliveryNoteById']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/create', [DeliveryNoteController::class, 'create']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/{id}', [DeliveryNoteController::class, 'update']);
        Route::middleware(CheckPermission::class . ':delete_document')->delete('/{id}', [DeliveryNoteController::class, 'delete']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/status/{id}', [DeliveryNoteController::class, 'updateStatus']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/generate-document', [DeliveryNoteController::class, 'generateDocument']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/generate-document-different-delivery', [DeliveryNoteController::class, 'generateDocumentDifferntDeliveryNote']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/statusitem/{id}', [DeliveryNoteController::class, 'updateItemsStatus']);
    });

    // Return Note routes
    Route::prefix('returnnotes')->group(function () {
        Route::middleware(CheckPermission::class . ':view_document')->post('/', [ReturnNoteController::class, 'getReturnNotes']);
        Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [ReturnNoteController::class, 'getReturnNotes']);
        Route::middleware(CheckPermission::class . ':view_document')->get('/{id}', [ReturnNoteController::class, 'getReturnNoteById']);
        Route::middleware(CheckPermission::class . ':view_archive')->get('/{id}/archive', [ReturnNoteController::class, 'getReturnNoteById']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/create', [ReturnNoteController::class, 'create']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/{id}', [ReturnNoteController::class, 'update']);
        Route::middleware(CheckPermission::class . ':delete_document')->delete('/{id}', [ReturnNoteController::class, 'delete']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/status/{id}', [ReturnNoteController::class, 'updateStatus']);
    });

    // Order Note routes
    Route::prefix('ordernotes')->group(function () {
        Route::middleware(CheckPermission::class . ':view_document')->post('/', [OrderNoteController::class, 'getOrderNotes']);
        Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [OrderNoteController::class, 'getOrderNotes']);
        Route::middleware(CheckPermission::class . ':view_document')->get('/{id}', [OrderNoteController::class, 'getOrderNoteById']);
        Route::middleware(CheckPermission::class . ':view_archive')->get('/{id}/archive', [OrderNoteController::class, 'getOrderNoteById']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/create', [OrderNoteController::class, 'create']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/{id}', [OrderNoteController::class, 'update']);
        Route::middleware(CheckPermission::class . ':delete_document')->delete('/{id}', [OrderNoteController::class, 'delete']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/status/{id}', [OrderNoteController::class, 'updateStatus']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/{id}/generate-document', [OrderNoteController::class, 'generateDocument']);
    });

    // Production Note routes
    Route::prefix('productionnotes')->group(function () {
        Route::middleware(CheckPermission::class . ':view_document')->post('/', [ProductionNoteController::class, 'getProductionNotes']);
        Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [ProductionNoteController::class, 'getProductionNotes']);
        Route::middleware(CheckPermission::class . ':view_document')->get('/{id}', [ProductionNoteController::class, 'getProductionNoteById']);
        Route::middleware(CheckPermission::class . ':view_archive')->get('/{id}/archive', [ProductionNoteController::class, 'getProductionNoteById']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/create', [ProductionNoteController::class, 'create']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/{id}', [ProductionNoteController::class, 'update']);
        Route::middleware(CheckPermission::class . ':delete_document')->delete('/{id}', [ProductionNoteController::class, 'delete']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/status/{id}', [ProductionNoteController::class, 'updateStatus']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/duplicate', [ProductionNoteController::class, 'duplicate']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/generate-document', [ProductionNoteController::class, 'generateDocument']);
    });

    // Static data routes
    Route::get('/staticdata', [StaticController::class, 'getAllData']);
    Route::get('/staticdatauser', [StaticController::class, 'getAllDataUser']);
    Route::get('/staticdatatransaction', [StaticController::class, 'getAllDataTransaction']);
    Route::get('/staticdatadocuments', [StaticController::class, 'getDocuments']);
    Route::get('/staticdatacontact', [StaticController::class, 'getContact']);
    Route::get('/staticdatapayement', [StaticController::class, 'getPaymentType']);
    Route::get('/staticdataclientforemail', [StaticController::class, 'getClienFortEmailSelect']);
    Route::get('/staticdataclientcontactforemail/{id}', [StaticController::class, 'getClientContactForEmailSelect']);
    Route::get('/staticdataclientdocumentcodesforemail/{id}/{type}', [StaticController::class, 'getClientDocumentCodesForEmailSelect']);
    Route::get('/staticdatatemplate', [StaticController::class, 'getTemplate']);
    Route::get('/staticdatarecoveries', [StaticController::class, 'getRecoveriesForPayment']);

    // Contact routes
    Route::prefix('contacts')->group(function () {
        Route::middleware(CheckPermission::class . ':view_client')->post('/', [ContactController::class, 'getContact']);
        Route::middleware(CheckPermission::class . ':view_client')->post('/{id}', [ContactController::class, 'getById']);
        Route::middleware(CheckPermission::class . ':add_client')->post('/create/contact', [ContactController::class, 'create']);
        Route::middleware(CheckPermission::class . ':edit_client')->put('/{id}', [ContactController::class, 'update']);
        Route::middleware(CheckPermission::class . ':delete_client')->delete('/{id}', [ContactController::class, 'delete']);
        Route::middleware(CheckPermission::class . ':view_logs')->post('/contactlogs/all', [LogController::class, 'getContactLogs']);
        Route::middleware(CheckPermission::class . ':view_logs')->get('/contactlogs/{id}', [LogController::class, 'getContactLogById']);
    });

    // Invoice routes
    Route::prefix('invoices')->group(function () {
        Route::middleware(CheckPermission::class . ':view_document')->post('/all', [InvoiceController::class, 'getInvoices']);
        Route::middleware(CheckPermission::class . ':view_archive')->post('/all/archive', [InvoiceController::class, 'getInvoices']);
        Route::middleware(CheckPermission::class . ':view_document')->get('/{id}', [InvoiceController::class, 'getInvoiceById']);
        Route::middleware(CheckPermission::class . ':view_archive')->get('/{id}/archive', [InvoiceController::class, 'getInvoiceById']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/create', [InvoiceController::class, 'create']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/{id}', [InvoiceController::class, 'update']);
        Route::middleware(CheckPermission::class . ':delete_document')->delete('/{id}', [InvoiceController::class, 'delete']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/status/{id}', [InvoiceController::class, 'updateStatus']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/{id}/generate-document', [InvoiceController::class, 'generateDocument']);
    });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::middleware(CheckPermission::class . ':view_payment')->post('/', [PaymentController::class, 'getAll']);
        Route::middleware(CheckPermission::class . ':view_payment')->post('/recoveries', [PaymentController::class, 'getAllRecoveries']);
        Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [PaymentController::class, 'getAll']);
        Route::middleware(CheckPermission::class . ':view_archive')->post('/recoveries/archive', [PaymentController::class, 'getAllRecoveries']);
        Route::middleware(CheckPermission::class . ':view_payment')->get('/{id}', [PaymentController::class, 'getPaymentById']);
        Route::middleware(CheckPermission::class . ':view_payment')->get('/recoveries/{id}', [PaymentController::class, 'getRecoveryById']);
        Route::middleware(CheckPermission::class . ':view_archive')->get('/{id}/archive', [PaymentController::class, 'getPaymentById']);
        Route::middleware(CheckPermission::class . ':view_archive')->get('/recoveries/{id}/archive', [PaymentController::class, 'getRecoveryById']);
        Route::middleware(CheckPermission::class . ':add_payment')->post('/create', [PaymentController::class, 'create']);
        Route::middleware(CheckPermission::class . ':add_payment')->post('/recoveries/create', [PaymentController::class, 'createRecovery']);
        Route::middleware(CheckPermission::class . ':edit_payment')->put('/{id}', [PaymentController::class, 'update']);
        Route::middleware(CheckPermission::class . ':edit_payment')->put('/recoveries/{id}', [PaymentController::class, 'updateRecovery']);
        Route::middleware(CheckPermission::class . ':delete_payment')->delete('/{id}', [PaymentController::class, 'delete']);
        Route::middleware(CheckPermission::class . ':delete_payment')->delete('/recoveries/{id}', [PaymentController::class, 'deleteRecovery']);
        Route::middleware(CheckPermission::class . ':view_logs')->post('/paymentlogs', [LogController::class, 'getPaymentLogs']);

        Route::middleware(CheckPermission::class . ':view_logs')->get('/paymentlogs/{id}', [LogController::class, 'getPaymentLogById']);
        Route::middleware(CheckPermission::class . ':view_logs')->post('/recoverylogs', [LogController::class, 'getRecoveryLogs']);
        Route::middleware(CheckPermission::class . ':view_logs')->get('/recoverylogs/{id}', [LogController::class, 'getRecoveryLogById']);
    });

    // Order Receipt routes
    Route::prefix('orderreceipts')->group(function () {
        Route::middleware(CheckPermission::class . ':view_document')->post('/', [OrderReceiptController::class, 'getorderReciept']);
        Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [OrderReceiptController::class, 'getorderReciept']);
        Route::middleware(CheckPermission::class . ':view_document')->get('/{id}', [OrderReceiptController::class, 'getOrderReceiptById']);
        Route::middleware(CheckPermission::class . ':view_archive')->get('/{id}/archive', [OrderReceiptController::class, 'getOrderReceiptById']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/create', [OrderReceiptController::class, 'create']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/{id}', [OrderReceiptController::class, 'update']);
        Route::middleware(CheckPermission::class . ':delete_document')->delete('/{id}', [OrderReceiptController::class, 'delete']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/status/{id}', [OrderReceiptController::class, 'updateStatus']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/{id}/generate-document', [OrderReceiptController::class, 'generateDocument']);
    });

    // Invoice Credit routes
    Route::prefix('invoicecredits')->group(function () {
        Route::middleware(CheckPermission::class . ':view_document')->post('/', [InvoiceCreditController::class, 'getInvoiceCredits']);
        Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [InvoiceCreditController::class, 'getInvoiceCredits']);
        Route::middleware(CheckPermission::class . ':view_document')->get('/{id}', [InvoiceCreditController::class, 'getInvoiceCreditsById']);
        Route::middleware(CheckPermission::class . ':view_archive')->get('/{id}/archive', [InvoiceCreditController::class, 'getInvoiceCreditsById']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/create', [InvoiceCreditController::class, 'create']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/{id}', [InvoiceCreditController::class, 'update']);
        Route::middleware(CheckPermission::class . ':delete_document')->delete('/{id}', [InvoiceCreditController::class, 'delete']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/status/{id}', [InvoiceCreditController::class, 'updateStatus']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/{id}/generate-document', [InvoiceCreditController::class, 'generateDocument']);
    });

    Route::get('/delivery/{id}/', [DeliveryNoteController::class, 'generatePdf']);
    Route::get('/invoice/{id}/', [InvoiceController::class, 'generatePdf']);
    Route::get('/quote/{id}/', [QuoteController::class, 'generatePdf']);
    Route::get('/output/{id}/', [OutputNoteController::class, 'generatePdf']);
    Route::get('/return/{id}/', [ReturnNoteController::class, 'generatePdf']);
    Route::get('/ordernote/{id}/', [OrderNoteController::class, 'generatePdf']);
    Route::get('/production/{id}/', [ProductionNoteController::class, 'generatePdf']);
    Route::get('/payment/{id}/', [PaymentController::class, 'generatePdf']);
    Route::get('/orderreciept/{id}/', [OrderReceiptController::class, 'generatePdf']);
    Route::get('/invoicecredit/{id}/', [InvoiceCreditController::class, 'generatePdf']);

    // Details routes
    Route::prefix('details')->group(function () {
        Route::middleware(CheckPermission::class . ':view_document')->get('/', [DetailController::class, 'index']);
        Route::middleware(CheckPermission::class . ':view_document')->get('/{id}', [DetailController::class, 'show']);
        Route::middleware(CheckPermission::class . ':add_document')->post('/', [DetailController::class, 'store']);
        Route::middleware(CheckPermission::class . ':edit_document')->put('/{id}', [DetailController::class, 'update']);
        Route::middleware(CheckPermission::class . ':delete_document')->delete('/{id}', [DetailController::class, 'destroy']);
    });

    // Email routes
    Route::post('/email/send-email', [EmailController::class, 'sendMail']);

    // Stats routes
    Route::middleware(CheckPermission::class . ':view_stats')->post('/clients/bybusiness', [ClientController::class, 'getClientsByBusinessSector']);
    Route::middleware(CheckPermission::class . ':view_stats')->post('/clients/stats/gamut', [ClientController::class, 'getClientsByGammut']);
    Route::middleware(CheckPermission::class . ':view_stats')->post('/clients/bytype', [ClientController::class, 'getClientsByType']);
    Route::middleware(CheckPermission::class . ':view_stats')->post('/payments/stats/total-turnover', [PaymentController::class, 'getTotalTurnover']);
    Route::middleware(CheckPermission::class . ':view_stats')->post('/payments/stats/invoice-turnover', [PaymentController::class, 'getInvoiceTurnover']);
    Route::middleware(CheckPermission::class . ':view_stats')->post('/payments/stats/order-receipt-turnover', [PaymentController::class, 'getOrderReceiptTurnover']);
    Route::middleware(CheckPermission::class . ':view_stats')->post('/payments/stats/real-turnover', [PaymentController::class, 'getRealTurnover']);
    Route::middleware(CheckPermission::class . ':view_stats')->post('/payments/stats/recovery', [PaymentController::class, 'getRecovery']);
    Route::middleware(CheckPermission::class . ':view_stats')->post('/payments/stats/total-turnover-by-client-type', [PaymentController::class, 'getTotalTurnoverByClientType']);
    Route::middleware(CheckPermission::class . ':view_stats')->post('/payments/stats/top-cities', [PaymentController::class, 'getTopCitiesByTurnover']);
    Route::middleware(CheckPermission::class . ':view_stats')->post('/payments/stats/top-activity-sectors', [PaymentController::class, 'getTopActivitySectorsByTurnover']);
    Route::middleware(CheckPermission::class . ':view_stats')->post('/payments/stats', [PaymentController::class, 'getTurnoverData']);
});

Route::post('/generate-document-navigation', [QuoteController::class, 'generateDocumentNavigation']);

// Refund Note routes
Route::prefix('refund-notes')->group(function () {
    Route::middleware(CheckPermission::class . ':view_document')->post('/', [RefundNoteController::class, 'getAll']);
    Route::middleware(CheckPermission::class . ':view_archive')->post('/archive', [RefundNoteController::class, 'getAll']);
    Route::middleware(CheckPermission::class . ':view_document')->get('/{id}', [RefundNoteController::class, 'getById']);
    Route::middleware(CheckPermission::class . ':view_archive')->get('/{id}/archive', [RefundNoteController::class, 'getById']);
    Route::middleware(CheckPermission::class . ':edit_document')->put('/{id}', [RefundNoteController::class, 'update']);
    Route::middleware(CheckPermission::class . ':delete_document')->delete('/{id}', [RefundNoteController::class, 'delete']);
    Route::middleware(CheckPermission::class . ':edit_document')->put('/status/{id}', [RefundNoteController::class, 'updateStatus']);
    Route::middleware(CheckPermission::class . ':edit_document')->put('/type/{id}', [RefundNoteController::class, 'updateType']);
});

// Log routes
Route::middleware(CheckPermission::class . ':view_logs')->post('/documentlogs', [LogController::class, 'getDocumentLogs']);
Route::middleware(CheckPermission::class . ':view_logs')->get('/documentlogs/{id}', [LogController::class, 'getDocumentLogById']);
Route::middleware(CheckPermission::class . ':view_logs')->post('/itemlogs', [LogController::class, 'getDocumentItemLogs']);
Route::middleware(CheckPermission::class . ':view_logs')->get('/itemlogs/{id}', [LogController::class, 'getDocumentItemLogById']);

// Additional email routes
Route::post('/email/send', [EmailController::class, 'sendMail']);
Route::post('/email/return-template', [EmailController::class, 'returnTemplate']);
Route::post('/emails', [EmailController::class, 'getAll']);

Route::get('/', function () {
    return response()->json(['message' => 'API is running'], 200);
});