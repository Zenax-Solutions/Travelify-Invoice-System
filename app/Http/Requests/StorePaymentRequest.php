<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $invoice = \App\Models\Invoice::find($this->invoice_id);
                    if ($invoice) {
                        $remainingBalance = $invoice->total_amount - $invoice->total_paid;
                        if ($value > $remainingBalance) {
                            $fail("Payment amount cannot exceed the remaining balance of " . number_format($remainingBalance, 2));
                        }
                    }
                },
            ],
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Payment amount must be greater than zero.',
            'payment_date.before_or_equal' => 'Payment date cannot be in the future.',
        ];
    }
}
