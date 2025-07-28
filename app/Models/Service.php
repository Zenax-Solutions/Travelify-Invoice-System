<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class)
            ->withPivot('quantity', 'unit_price')
            ->withTimestamps();
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    // Get all vendors that provide this service
    public function getVendorsAttribute()
    {
        return $this->purchaseOrderItems()
            ->with(['purchaseOrder.vendor'])
            ->get()
            ->pluck('purchaseOrder.vendor')
            ->filter(function ($vendor) {
                return $vendor !== null;
            })
            ->unique('id');
    }

    // Get the primary vendor for this service (most recent purchase order)
    public function getPrimaryVendorAttribute()
    {
        $latestPurchaseOrderItem = $this->purchaseOrderItems()
            ->with(['purchaseOrder.vendor'])
            ->whereHas('purchaseOrder', function ($query) {
                $query->whereNotNull('vendor_id');
            })
            ->orderBy('created_at', 'desc')
            ->first();

        return $latestPurchaseOrderItem?->purchaseOrder?->vendor ?? null;
    }

    // Get credit warning information for this service
    public function getCreditWarningInfoAttribute()
    {
        try {
            $vendor = $this->getPrimaryVendorAttribute();

            if (!$vendor || !$vendor->credit_limit || $vendor->credit_limit <= 0) {
                return null;
            }

            $creditUsage = $vendor->credit_usage_percentage ?? 0;

            return [
                'vendor_name' => $vendor->name,
                'credit_usage' => $creditUsage,
                'available_credit' => $vendor->available_credit ?? 0,
                'is_high_risk' => $creditUsage >= 90,
                'is_medium_risk' => $creditUsage >= 75 && $creditUsage < 90,
                'status_color' => $creditUsage >= 90 ? 'red' : ($creditUsage >= 75 ? 'yellow' : 'green'),
                'status_icon' => $creditUsage >= 90 ? '🔴' : ($creditUsage >= 75 ? '🟡' : '🟢'),
            ];
        } catch (\Exception $e) {
            Log::error('Error getting credit warning info for service ' . $this->id . ': ' . $e->getMessage());
            return null;
        }
    }

    // Debug method to check service-vendor relationships
    public function debugCreditInfo()
    {
        $debug = [
            'service_id' => $this->id,
            'service_name' => $this->name,
            'purchase_order_items_count' => $this->purchaseOrderItems()->count(),
            'purchase_orders_with_vendors' => [],
            'primary_vendor' => null,
            'credit_info' => null,
        ];

        // Check purchase order items
        $purchaseOrderItems = $this->purchaseOrderItems()->with(['purchaseOrder.vendor'])->get();
        foreach ($purchaseOrderItems as $item) {
            if ($item->purchaseOrder && $item->purchaseOrder->vendor) {
                $vendor = $item->purchaseOrder->vendor;
                $debug['purchase_orders_with_vendors'][] = [
                    'po_id' => $item->purchaseOrder->id,
                    'vendor_id' => $vendor->id,
                    'vendor_name' => $vendor->name,
                    'credit_limit' => $vendor->credit_limit,
                    'created_at' => $item->created_at,
                ];
            }
        }

        // Get primary vendor
        $primaryVendor = $this->getPrimaryVendorAttribute();
        if ($primaryVendor) {
            $debug['primary_vendor'] = [
                'id' => $primaryVendor->id,
                'name' => $primaryVendor->name,
                'credit_limit' => $primaryVendor->credit_limit,
                'outstanding_balance' => $primaryVendor->outstanding_balance,
                'credit_usage_percentage' => $primaryVendor->credit_usage_percentage,
            ];
        }

        // Get credit info
        $debug['credit_info'] = $this->getCreditWarningInfoAttribute();

        return $debug;
    }
}
