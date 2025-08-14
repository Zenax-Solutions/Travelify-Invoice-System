<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->enum('penalty_type', [
                'date_change',
                'cancellation',
                'late_booking',
                'no_show',
                'amendment_fee',
                'supplier_penalty',
                'other'
            ]);

            // Date tracking
            $table->date('original_tour_date')->nullable();
            $table->date('new_tour_date')->nullable();
            $table->date('penalty_date')->default(now());

            // Financial details
            $table->decimal('penalty_amount', 10, 2);
            $table->enum('penalty_bearer', ['customer', 'agency', 'shared'])->default('customer');
            $table->decimal('customer_amount', 10, 2)->default(0); // Amount charged to customer
            $table->decimal('agency_amount', 10, 2)->default(0);   // Amount absorbed by agency

            // Documentation
            $table->string('supplier_name')->nullable(); // Which supplier imposed penalty
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable(); // For penalty invoices/documents

            // Status and approval
            $table->enum('status', ['pending', 'approved', 'applied', 'waived', 'disputed'])->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            // Financial integration
            $table->boolean('invoice_updated')->default(false);
            $table->boolean('expense_recorded')->default(false);
            $table->foreignId('expense_id')->nullable(); // Link to expense record

            $table->timestamps();

            // Indexes for better performance
            $table->index(['invoice_id', 'status']);
            $table->index(['penalty_type', 'penalty_date']);
        });

        // Add penalty tracking to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('total_penalties', 10, 2)->default(0.00)->after('total_amount');
            $table->json('penalty_summary')->nullable()->after('total_penalties');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['total_penalties', 'penalty_summary']);
        });

        Schema::dropIfExists('penalties');
    }
};
