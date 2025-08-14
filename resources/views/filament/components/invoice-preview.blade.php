<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-4" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Invoice Preview
        </h3>
        <span class="px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded-full">
            {{ $invoice->status }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Customer Information -->
        <div class="space-y-3">
            <h4 class="text-sm font-medium text-gray-700 flex items-center">
                <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Customer Details
            </h4>
            <div class="bg-white rounded-md p-4 border border-gray-100">
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Name:</span>
                        <span class="text-sm font-medium text-gray-900">{{ $invoice->customer->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Email:</span>
                        <span class="text-sm text-gray-700">{{ $invoice->customer->email }}</span>
                    </div>
                    @if($invoice->customer->phone)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Phone:</span>
                        <span class="text-sm text-gray-700">{{ $invoice->customer->phone }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Invoice Information -->
        <div class="space-y-3">
            <h4 class="text-sm font-medium text-gray-700 flex items-center">
                <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Invoice Information
            </h4>
            <div class="bg-white rounded-md p-4 border border-gray-100">
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Invoice #:</span>
                        <span class="text-sm font-medium text-gray-900">{{ $invoice->invoice_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Date:</span>
                        <span class="text-sm text-gray-700">{{ $invoice->invoice_date->format('d M Y') }}</span>
                    </div>
                    @if($invoice->tour_date)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Tour Date:</span>
                        <span class="text-sm text-gray-700">{{ $invoice->tour_date->format('d M Y') }}</span>
                    </div>
                    @else
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Type:</span>
                        <span class="text-sm text-orange-600 font-medium">Visa/Service Only</span>
                    </div>
                    @endif
                    @if($invoice->due_date)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Due Date:</span>
                        <span class="text-sm text-gray-700">{{ $invoice->due_date->format('d M Y') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="mt-6">
        <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
            <svg class="w-4 h-4 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
            Financial Summary
        </h4>
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-md p-4 border border-blue-100">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-xs text-gray-500 mb-1">Original Amount</div>
                    <div class="text-lg font-semibold text-gray-900">Rs {{ number_format($invoice->total_amount, 2) }}</div>
                </div>

                @if($invoice->total_refunded > 0)
                <div class="text-center">
                    <div class="text-xs text-gray-500 mb-1">Refunded</div>
                    <div class="text-lg font-semibold text-red-600">Rs {{ number_format($invoice->total_refunded, 2) }}</div>
                </div>
                @endif

                @if($invoice->total_penalties > 0)
                <div class="text-center">
                    <div class="text-xs text-gray-500 mb-1">Penalties</div>
                    <div class="text-lg font-semibold text-orange-600">Rs {{ number_format($invoice->total_penalties, 2) }}</div>
                </div>
                @endif

                <div class="text-center">
                    <div class="text-xs text-gray-500 mb-1">Paid Amount</div>
                    <div class="text-lg font-semibold text-green-600">Rs {{ number_format($totalPaid, 2) }}</div>
                </div>

                <div class="text-center">
                    <div class="text-xs text-gray-500 mb-1">Outstanding</div>
                    <div class="text-lg font-semibold {{ $remainingBalance > 0 ? 'text-red-600' : 'text-green-600' }}">
                        Rs {{ number_format($remainingBalance, 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Services Breakdown -->
    @if($invoice->services && $invoice->services->count() > 0)
    <div class="mt-6">
        <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
            <svg class="w-4 h-4 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            Services Included
        </h4>
        <div class="bg-white rounded-md border border-gray-100 overflow-hidden">
            <div class="max-h-32 overflow-y-auto">
                @foreach($invoice->services as $service)
                <div class="px-4 py-2 border-b border-gray-50 last:border-b-0">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">{{ $service->name }}</span>
                        <span class="text-sm font-medium text-gray-900">
                            {{ $service->pivot->quantity }}x Rs {{ number_format($service->pivot->unit_price, 2) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Warning for Date Change Penalties -->
    @if(!$invoice->tour_date)
    <div class="mt-4 p-3 bg-orange-50 border border-orange-200 rounded-md">
        <div class="flex items-center">
            <svg class="w-4 h-4 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <span class="text-sm text-orange-800">
                <strong>Note:</strong> This appears to be a visa/service-only invoice without a tour date. Date change penalties may not apply.
            </span>
        </div>
    </div>
    @endif
</div>