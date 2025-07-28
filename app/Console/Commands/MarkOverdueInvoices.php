<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:mark-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark invoices as overdue if they have passed their due date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        $overdueInvoices = Invoice::where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'overdue')
            ->where('due_date', '<', $today)
            ->get();

        $count = 0;
        foreach ($overdueInvoices as $invoice) {
            // Only mark as overdue if not fully paid
            if ($invoice->total_paid < $invoice->total_amount) {
                $invoice->update(['status' => 'overdue']);
                $count++;
            }
        }

        $this->info("Marked {$count} invoices as overdue.");

        return Command::SUCCESS;
    }
}
