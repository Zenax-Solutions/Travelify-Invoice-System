<?php

namespace App\Observers;

use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class InvoiceItemObserver
{
    /**
     * Handle the InvoiceItem "created" event.
     */
    public function created(InvoiceItem $invoiceItem): void
    {
        $this->recalculateInvoiceTotal($invoiceItem);
    }

    /**
     * Handle the InvoiceItem "updated" event.
     */
    public function updated(InvoiceItem $invoiceItem): void
    {
        $this->recalculateInvoiceTotal($invoiceItem);
    }

    /**
     * Handle the InvoiceItem "deleted" event.
     */
    public function deleted(InvoiceItem $invoiceItem): void
    {
        $this->recalculateInvoiceTotal($invoiceItem);
    }

    /**
     * Handle the InvoiceItem "restored" event.
     */
    public function restored(InvoiceItem $invoiceItem): void
    {
        $this->recalculateInvoiceTotal($invoiceItem);
    }

    /**
     * Recalculate the invoice total amount
     */
    private function recalculateInvoiceTotal(InvoiceItem $invoiceItem): void
    {
        $invoice = $invoiceItem->invoice;
        if ($invoice) {
            $totalAmount = $invoice->items()->sum(DB::raw('quantity * unit_price'));
            $invoice->update(['total_amount' => $totalAmount]);
        }
    }
}
