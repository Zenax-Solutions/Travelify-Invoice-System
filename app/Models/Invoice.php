<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'customer_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'tour_date',
        'total_amount',
        'total_refunded',
        'net_amount',
        'status',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'tour_date' => 'date',
        'cancelled_at' => 'date',
        'total_amount' => 'decimal:2',
        'total_refunded' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot('quantity', 'unit_price')
            ->withTimestamps();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(InvoiceRefund::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // Accessor to calculate remaining balance (considering refunds)
    public function getRemainingBalanceAttribute(): float
    {
        return $this->net_amount - $this->total_paid;
    }

    // Accessor for net amount (total - refunds)
    public function getNetAmountAttribute(): float
    {
        return $this->total_amount - $this->total_refunded;
    }

    // Check if invoice is cancelled
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled' && !is_null($this->cancelled_at);
    }

    // Check if invoice is refundable
    public function isRefundable(): bool
    {
        return !in_array($this->status, ['cancelled', 'draft']) &&
            $this->total_paid > 0 &&
            $this->total_refunded < $this->total_paid;
    }

    // Get available refund amount
    public function getAvailableRefundAmountAttribute(): float
    {
        return $this->total_paid - $this->total_refunded;
    }

    // Method to cancel invoice
    public function cancel(string $reason, int $cancelledBy): bool
    {
        if ($this->isCancelled()) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);

        $this->updateTotalPaidAndStatus();
        return true;
    }

    // Method to process refund
    public function processRefund(float $amount, string $reason, string $method, int $processedBy): InvoiceRefund
    {
        if (!$this->isRefundable() || $amount > $this->available_refund_amount) {
            throw new \InvalidArgumentException('Invalid refund amount or invoice not refundable');
        }

        $refund = $this->refunds()->create([
            'refund_amount' => $amount,
            'refund_reason' => $reason,
            'refund_method' => $method,
            'refund_date' => now(),
            'status' => 'processed',
            'processed_by' => $processedBy,
            'processed_at' => now(),
        ]);

        $this->updateRefundTotals();
        return $refund;
    }

    // Update refund totals and recalculate status
    public function updateRefundTotals(): void
    {
        $this->total_refunded = $this->refunds()->where('status', 'processed')->sum('refund_amount');
        $this->net_amount = $this->total_amount - $this->total_refunded;
        $this->save();

        $this->updateTotalPaidAndStatus();
    }

    // Method to update total_paid and status (enhanced for refunds)
    public function updateTotalPaidAndStatus(): void
    {
        $this->total_paid = $this->payments()->sum('amount');

        // Handle cancelled invoices
        if ($this->isCancelled()) {
            $this->status = 'cancelled';
        } else {
            // Calculate effective payment against net amount
            $effectiveBalance = $this->net_amount - $this->total_paid;

            if ($effectiveBalance <= 0) {
                $this->status = 'paid';
            } elseif ($this->total_paid > 0) {
                $this->status = 'partially_paid';
            } else {
                $this->status = 'pending';
            }

            // Handle fully refunded cases
            if ($this->total_refunded >= $this->total_amount) {
                $this->status = 'refunded';
            }
        }

        $this->save();
    }
}
