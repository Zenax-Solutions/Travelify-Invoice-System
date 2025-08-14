<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Penalty extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'invoice_id',
        'penalty_type',
        'original_tour_date',
        'new_tour_date',
        'penalty_date',
        'penalty_amount',
        'penalty_bearer',
        'customer_amount',
        'agency_amount',
        'supplier_name',
        'reason',
        'notes',
        'attachments',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'invoice_updated',
        'expense_recorded',
        'expense_id'
    ];

    protected $casts = [
        'original_tour_date' => 'date',
        'new_tour_date' => 'date',
        'penalty_date' => 'date',
        'penalty_amount' => 'decimal:2',
        'customer_amount' => 'decimal:2',
        'agency_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'attachments' => 'array',
        'invoice_updated' => 'boolean',
        'expense_recorded' => 'boolean'
    ];

    // Relationships
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(VendorPayment::class, 'expense_id');
    }

    // Accessors
    public function getPenaltyTypeDescriptionAttribute(): string
    {
        return match ($this->penalty_type) {
            'date_change' => 'Date Change Penalty',
            'cancellation' => 'Cancellation Fee',
            'late_booking' => 'Late Booking Fee',
            'no_show' => 'No Show Penalty',
            'amendment_fee' => 'Amendment Fee',
            'supplier_penalty' => 'Supplier Penalty',
            'other' => 'Other Penalty',
            default => 'Unknown Penalty'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'applied' => 'success',
            'waived' => 'secondary',
            'disputed' => 'danger',
            default => 'gray'
        };
    }

    public function getBearerColorAttribute(): string
    {
        return match ($this->penalty_bearer) {
            'customer' => 'success',
            'agency' => 'danger',
            'shared' => 'warning',
            default => 'gray'
        };
    }

    public function getDaysChangedAttribute(): ?int
    {
        if (!$this->original_tour_date || !$this->new_tour_date) {
            return null;
        }

        return $this->original_tour_date->diffInDays($this->new_tour_date);
    }

    // Business Logic Methods
    public function approve(int $approvedById = null): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedById ?? Auth::id(),
            'approved_at' => now()
        ]);

        return true;
    }

    public function applyToInvoice(): bool
    {
        if ($this->status !== 'approved' || $this->invoice_updated) {
            return false;
        }

        // Update invoice with customer portion
        if ($this->customer_amount > 0) {
            $this->invoice->increment('total_amount', $this->customer_amount);
            $this->invoice->increment('total_penalties', $this->customer_amount);
        }

        // Update penalty summary
        $penaltySummary = $this->invoice->penalty_summary ?? [];
        $penaltySummary[] = [
            'penalty_id' => $this->id,
            'type' => $this->penalty_type,
            'amount' => $this->customer_amount,
            'date' => $this->penalty_date->toDateString(),
            'reason' => $this->reason
        ];

        $this->invoice->update(['penalty_summary' => $penaltySummary]);

        // Recalculate invoice status with new totals
        $this->invoice->updateTotalPaidAndStatus();

        $this->update([
            'status' => 'applied',
            'invoice_updated' => true
        ]);

        return true;
    }

    public function recordAsExpense(): bool
    {
        if ($this->expense_recorded || $this->agency_amount <= 0) {
            return false;
        }

        // Create expense record (you might want to create a specific expense type)
        $expense = VendorPayment::create([
            'vendor_id' => null, // You might want to create a "Internal Penalties" vendor
            'purchase_order_id' => null,
            'amount' => $this->agency_amount,
            'payment_date' => $this->penalty_date,
            'payment_method' => 'penalty_absorption',
            'description' => "Penalty absorbed for Invoice #{$this->invoice->invoice_number}: {$this->reason}",
            'status' => 'completed'
        ]);

        $this->update([
            'expense_recorded' => true,
            'expense_id' => $expense->id
        ]);

        return true;
    }

    public function waive(string $reason = null): bool
    {
        if (!in_array($this->status, ['pending', 'approved'])) {
            return false;
        }

        $this->update([
            'status' => 'waived',
            'notes' => ($this->notes ? $this->notes . "\n\n" : '') . "WAIVED: " . ($reason ?? 'No reason provided'),
            'approved_by' => Auth::id(),
            'approved_at' => now()
        ]);

        return true;
    }

    // Static methods for reporting
    public static function getTotalPenaltiesByPeriod($startDate, $endDate)
    {
        return static::whereBetween('penalty_date', [$startDate, $endDate])
            ->where('status', 'applied')
            ->selectRaw('
                penalty_type,
                penalty_bearer,
                COUNT(*) as count,
                SUM(penalty_amount) as total_amount,
                SUM(customer_amount) as total_customer,
                SUM(agency_amount) as total_agency
            ')
            ->groupBy('penalty_type', 'penalty_bearer')
            ->get();
    }

    public static function getPenaltyTrends($months = 12)
    {
        return static::selectRaw('
                DATE_FORMAT(penalty_date, "%Y-%m") as month,
                penalty_type,
                COUNT(*) as count,
                SUM(penalty_amount) as total_amount
            ')
            ->where('penalty_date', '>=', now()->subMonths($months))
            ->where('status', 'applied')
            ->groupBy('month', 'penalty_type')
            ->orderBy('month')
            ->get();
    }
}
