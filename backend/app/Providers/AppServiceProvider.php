<?php

namespace App\Providers;

use App\Http\Controllers\PaymentLogController;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\ClientRepository;
use App\Repositories\Contracts\StaticRepositoryInterface;
use App\Repositories\StaticRepository;
use App\Repositories\Contracts\ClientLogRepositoryInterface;
use App\Repositories\ClientLogRepository;
use App\Repositories\Contracts\UserLogRepositoryInterface;
use App\Repositories\UserLogRepository;
use App\Repositories\Contracts\CashRegisterRepositoryInterface;
use App\Repositories\CashRegisterRepository;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\TransactionRepository;
use App\Repositories\Contracts\CashLogRepositoryInterface;
use App\Repositories\CashLogRepository;
use App\Repositories\CashRegisterDailyBalancesRepository;
use App\Repositories\Contracts\CashRegisterDailyBalancesRepositoryInterface;
use App\Repositories\Contracts\DeliveryNoteRepositoryInterface;
use App\Repositories\Contracts\DocumentItemRepositoryInterface;
use App\Repositories\Contracts\InvoiceCreditRepositoryInterface;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\OrderNoteRepositoryInterface;
use App\Repositories\Contracts\OutputNoteRepositoryInterface;
use App\Repositories\Contracts\ProductionNoteRepositoryInterface;
use App\Repositories\Contracts\QuoteRepositoryInterface;
use App\Repositories\Contracts\ReturnNoteRepositoryInterface;
use App\Repositories\DeliveryNoteRepository;
use App\Repositories\DocumentItemRepository;
use App\Repositories\Contracts\OrderReceiptRepositoryInterface;
use App\Repositories\InvoiceCreditRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\OutputNoteRepository;
use App\Repositories\ProductionNoteRepository;
use App\Repositories\QuoteRepository;
use App\Repositories\ReturnNoteRepository;
use App\Repositories\OrderNoteRepository;
use App\Repositories\OrderReceiptRepository;
use App\Repositories\ContactLogRepository;
use App\Repositories\Contracts\ContactLogRepositoryInterface;
use App\Repositories\ContactRepository;
use App\Repositories\Contracts\ContactRepositoryInterface;
use App\Repositories\Contracts\PaymentLogRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\RecoveryLogRepositoryInterface;
use App\Repositories\Contracts\DetailRepositoryInterface;
use App\Repositories\Contracts\ItemRepositoryInterface;
use App\Repositories\PaymentLogRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\RecoveryLogRepository;
use App\Repositories\ItemRepository;
use App\Repositories\QuoteItemRepository;
use App\Repositories\InvoiceItemRepository;
use App\Repositories\OrderNoteItemRepository;
use App\Repositories\ProductionNoteItemRepository;
use App\Repositories\OutputNoteItemRepository;
use App\Repositories\DeliveryNoteItemRepository;
use App\Repositories\ReturnNoteItemRepository;
use App\Repositories\InvoiceCreditItemRepository;
use App\Repositories\OrderReceiptItemRepository;
use App\Repositories\Contracts\QuoteItemRepositoryInterface;
use App\Repositories\Contracts\InvoiceItemRepositoryInterface;
use App\Repositories\Contracts\OrderNoteItemRepositoryInterface;
use App\Repositories\Contracts\ProductionNoteItemRepositoryInterface;
use App\Repositories\Contracts\OutputNoteItemRepositoryInterface;
use App\Repositories\Contracts\DeliveryNoteItemRepositoryInterface;
use App\Repositories\Contracts\ReturnNoteItemRepositoryInterface;
use App\Repositories\Contracts\InvoiceCreditItemRepositoryInterface;
use App\Repositories\Contracts\OrderReceiptItemRepositoryInterface;
use App\Repositories\Contracts\EmailRepositoryInterface;
use App\Repositories\Contracts\QuoteRequestRepositoryInterface;
use App\Repositories\DetailRepository;
use App\Repositories\Contracts\RefundNoteRepositoryInterface;
use App\Repositories\EmailRepository;
use App\Repositories\QuoteRequestRepository;
use App\Repositories\RefundNoteRepository;
use App\Services\DocumentLogger;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(QuoteItemRepositoryInterface::class, QuoteItemRepository::class);
        $this->app->bind(InvoiceItemRepositoryInterface::class, InvoiceItemRepository::class);
        $this->app->bind(OrderNoteItemRepositoryInterface::class, OrderNoteItemRepository::class);
        $this->app->bind(ProductionNoteItemRepositoryInterface::class, ProductionNoteItemRepository::class);
        $this->app->bind(OutputNoteItemRepositoryInterface::class, OutputNoteItemRepository::class);
        $this->app->bind(DeliveryNoteItemRepositoryInterface::class, DeliveryNoteItemRepository::class);
        $this->app->bind(ReturnNoteItemRepositoryInterface::class, ReturnNoteItemRepository::class);
        $this->app->bind(InvoiceCreditItemRepositoryInterface::class, InvoiceCreditItemRepository::class);
        $this->app->bind(OrderReceiptItemRepositoryInterface::class, OrderReceiptItemRepository::class);
        $this->app->bind(ItemRepositoryInterface::class, ItemRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ClientRepositoryInterface::class, ClientRepository::class);
        $this->app->bind(StaticRepositoryInterface::class, StaticRepository::class);
        $this->app->bind(ClientLogRepositoryInterface::class, ClientLogRepository::class);
        $this->app->bind(UserLogRepositoryInterface::class, UserLogRepository::class);
        $this->app->bind(CashRegisterRepositoryInterface::class, CashRegisterRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
        $this->app->bind(CashLogRepositoryInterface::class, CashLogRepository::class);
        $this->app->bind(CashRegisterDailyBalancesRepositoryInterface::class, CashRegisterDailyBalancesRepository::class);
        $this->app->bind(QuoteRepositoryInterface::class, QuoteRepository::class);
        $this->app->bind(OrderNoteRepositoryInterface::class, OrderNoteRepository::class);
        $this->app->bind(ProductionNoteRepositoryInterface::class, ProductionNoteRepository::class);
        $this->app->bind(OutputNoteRepositoryInterface::class, OutputNoteRepository::class);
        $this->app->bind(DeliveryNoteRepositoryInterface::class, DeliveryNoteRepository::class);
        $this->app->bind(ReturnNoteRepositoryInterface::class, ReturnNoteRepository::class);
        $this->app->bind(InvoiceCreditRepositoryInterface::class, InvoiceCreditRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->bind(DocumentItemRepositoryInterface::class, DocumentItemRepository::class);
        $this->app->bind(OrderReceiptRepositoryInterface::class, OrderReceiptRepository::class);
        $this->app->bind(ContactRepositoryInterface::class, ContactRepository::class);
        $this->app->bind(ContactLogRepositoryInterface::class, ContactLogRepository::class);
        $this->app->bind(DetailRepositoryInterface::class, DetailRepository::class);
        $this->app->bind(RefundNoteRepositoryInterface::class, RefundNoteRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->singleton(DocumentLogger::class, function ($app) {
            return new DocumentLogger();
        });
        $this->app->bind(PaymentLogRepositoryInterface::class, PaymentLogRepository::class);
        $this->app->bind(RecoveryLogRepositoryInterface::class, RecoveryLogRepository::class);
        $this->app->bind(QuoteRequestRepositoryInterface::class, QuoteRequestRepository::class);
        $this->app->bind(EmailRepositoryInterface::class, EmailRepository::class);
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    public function boot()
    {
        //
    }
}
