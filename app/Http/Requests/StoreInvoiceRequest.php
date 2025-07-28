<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // You can implement proper authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'invoice_number' => 'required|string|max:255|unique:invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'tour_date' => 'nullable|date',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid,overdue,cancelled,partially_paid',
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.quantity' => 'required|integer|min:1',
            'services.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'due_date.after_or_equal' => 'The due date must be on or after the invoice date.',
            'services.required' => 'At least one service is required.',
            'services.*.quantity.min' => 'Quantity must be at least 1.',
            'services.*.unit_price.min' => 'Unit price must be a positive number.',
        ];
    }
}
