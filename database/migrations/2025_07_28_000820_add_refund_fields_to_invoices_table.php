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
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('total_refunded', 10, 2)->default(0)->after('total_paid');
            $table->decimal('net_amount', 10, 2)->default(0)->after('total_refunded');
            $table->date('cancelled_at')->nullable()->after('status');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancellation_reason');

            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'cancelled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropIndex(['status', 'cancelled_at']);
            $table->dropColumn([
                'total_refunded',
                'net_amount',
                'cancelled_at',
                'cancellation_reason',
                'cancelled_by'
            ]);
        });
    }
};
