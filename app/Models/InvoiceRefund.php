<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceRefund extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'invoice_id',
        'refund_number',
        'refund_amount',
        'refund_reason',
        'refund_method',
        'refund_date',
        'status',
        'notes',
        'processed_by',
        'processed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'refund_date' => 'date',
        'processed_at' => 'datetime',
        'refund_amount' => 'decimal:2',
    ];

    protected $with = ['invoice', 'processedBy'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Generate unique refund number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($refund) {
            if (empty($refund->refund_number)) {
                $refund->refund_number = 'REF-' . str_pad(
                    static::count() + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}
