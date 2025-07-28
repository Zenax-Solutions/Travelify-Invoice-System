<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'po_number',
        'po_date',
        'total_amount',
        'status',
        'notes',
        'total_paid',
    ];

    protected $casts = [
        'po_date' => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    // Accessor to calculate remaining balance
    public function getRemainingBalanceAttribute(): float
    {
        return $this->total_amount - $this->total_paid;
    }

    // Method to update total_paid and status
    public function updateTotalPaidAndStatus(): void
    {
        $this->total_paid = $this->payments()->sum('amount');

        if ($this->total_paid >= $this->total_amount) {
            $this->status = 'paid';
        } elseif ($this->total_paid > 0) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'pending';
        }

        $this->save();
    }

    // Method to calculate and update total amount from items
    public function calculateTotalAmount(): float
    {
        $totalAmount = $this->items()->sum('total');
        $this->update(['total_amount' => $totalAmount]);
        return $totalAmount;
    }

    // Accessor to get total amount calculated from items if needed
    public function getTotalAmountFromItemsAttribute(): float
    {
        return $this->items()->sum('total');
    }
}
