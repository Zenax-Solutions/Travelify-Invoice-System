<?php

namespace App\Providers;

use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\VendorPayment;
use App\Observers\InvoiceItemObserver;
use App\Observers\PaymentObserver;
use App\Observers\VendorPaymentObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Payment::observe(PaymentObserver::class);
        VendorPayment::observe(VendorPaymentObserver::class);
        InvoiceItem::observe(InvoiceItemObserver::class);
    }
}
