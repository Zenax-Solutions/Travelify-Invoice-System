<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'credit_limit',
        'is_service_provider',
        'notes',
    ];

    protected $casts = [
        'is_service_provider' => 'boolean',
        'credit_limit' => 'decimal:2',
    ];

    // Get outstanding balance in LKR (sum of unpaid purchase orders)
    public function getOutstandingBalanceAttribute(): float
    {
        return $this->purchaseOrders()
            ->where('status', '!=', 'paid')
            ->sum('total_amount') - $this->purchaseOrders()->where('status', '!=', 'paid')->sum('total_paid');
    }

    // Get credit usage percentage
    public function getCreditUsagePercentageAttribute(): float
    {
        if (!$this->credit_limit || $this->credit_limit <= 0) {
            return 0;
        }

        return min(($this->outstanding_balance / $this->credit_limit) * 100, 100);
    }

    // Check if credit limit is exceeded
    public function isOverCreditLimitAttribute(): bool
    {
        if (!$this->credit_limit || $this->credit_limit <= 0) {
            return false;
        }

        return $this->outstanding_balance > $this->credit_limit;
    }

    // Get available credit in LKR
    public function getAvailableCreditAttribute(): float
    {
        if (!$this->credit_limit || $this->credit_limit <= 0) {
            return 0;
        }

        return max($this->credit_limit - $this->outstanding_balance, 0);
    }

    // Check if a new order amount would exceed credit limit (LKR amounts)
    public function wouldExceedCreditLimit(float $lkrAmount): bool
    {
        if (!$this->credit_limit || $this->credit_limit <= 0) {
            return false;
        }

        $newBalance = $this->outstanding_balance + $lkrAmount;

        return $newBalance > $this->credit_limit;
    }

    // Get credit limit overage for a given LKR amount
    public function getCreditLimitOverage(float $lkrAmount): float
    {
        if (!$this->credit_limit || $this->credit_limit <= 0) {
            return 0;
        }

        $newBalance = $this->outstanding_balance + $lkrAmount;

        return max($newBalance - $this->credit_limit, 0);
    }

    // Relationship to purchase orders
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
