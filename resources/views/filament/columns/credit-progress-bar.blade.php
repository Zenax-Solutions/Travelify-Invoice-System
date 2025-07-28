<div class="flex flex-col space-y-1">
    @if($getRecord()->credit_limit > 0)
    @php
    $percentage = $getRecord()->credit_usage_percentage;
    $isOverLimit = $getRecord()->is_over_credit_limit;
    $barColor = $percentage >= 90 ? 'bg-red-500' : ($percentage >= 75 ? 'bg-yellow-500' : 'bg-green-500');
    $textColor = $percentage >= 90 ? 'text-red-600' : ($percentage >= 75 ? 'text-yellow-600' : 'text-green-600');
    @endphp

    <div class="w-full bg-gray-200 rounded-full h-2">
        <div class="{{ $barColor }} h-2 rounded-full transition-all duration-300"
            style="width: {{ min($percentage, 100) }}%"></div>
    </div>

    <div class="flex justify-between text-xs {{ $textColor }}">
        <span class="font-medium">{{ number_format($percentage, 1) }}%</span>
        <span>
            Rs{{ number_format($getRecord()->outstanding_balance, 2) }} /
            Rs{{ number_format($getRecord()->credit_limit, 2) }}
        </span>
    </div>

    @if($isOverLimit)
    <div class="flex items-center space-x-1 text-xs text-red-600">
        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
        </svg>
        <span class="font-medium">OVER LIMIT</span>
    </div>
    @elseif($percentage >= 90)
    <div class="flex items-center space-x-1 text-xs text-orange-600">
        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
        </svg>
        <span class="font-medium">NEAR LIMIT</span>
    </div>
    @endif
    @else
    <div class="text-xs text-gray-500">No credit limit set</div>
    @endif
</div>