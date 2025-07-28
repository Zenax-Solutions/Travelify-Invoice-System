<?php

namespace App\Observers;

use App\Models\VendorPayment;

class VendorPaymentObserver
{
    /**
     * Handle the VendorPayment "created" event.
     */
    public function created(VendorPayment $vendorPayment): void
    {
        $vendorPayment->purchaseOrder->updateTotalPaidAndStatus();
    }

    /**
     * Handle the VendorPayment "updated" event.
     */
    public function updated(VendorPayment $vendorPayment): void
    {
        $vendorPayment->purchaseOrder->updateTotalPaidAndStatus();
    }

    /**
     * Handle the VendorPayment "deleted" event.
     */
    public function deleted(VendorPayment $vendorPayment): void
    {
        $vendorPayment->purchaseOrder->updateTotalPaidAndStatus();
    }

    /**
     * Handle the VendorPayment "restored" event.
     */
    public function restored(VendorPayment $vendorPayment): void
    {
        $vendorPayment->purchaseOrder->updateTotalPaidAndStatus();
    }

    /**
     * Handle the VendorPayment "force deleted" event.
     */
    public function forceDeleted(VendorPayment $vendorPayment): void
    {
        //
    }
}
