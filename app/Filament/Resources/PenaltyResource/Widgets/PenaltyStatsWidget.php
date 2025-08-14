<?php

namespace App\Filament\Resources\PenaltyResource\Widgets;

use App\Models\Penalty;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PenaltyStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Pending Approvals', $this->getPendingCount())
                ->description('Penalties awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('This Month Total', $this->getThisMonthTotal())
                ->description('Total penalties this month')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Customer Charges', $this->getCustomerCharges())
                ->description('Amount charged to customers')
                ->descriptionIcon('heroicon-m-currency-rupee')
                ->color('success'),

            Stat::make('Agency Absorption', $this->getAgencyAbsorption())
                ->description('Amount absorbed by agency')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),
        ];
    }

    private function getPendingCount(): int
    {
        return Penalty::where('status', 'pending')->count();
    }

    private function getThisMonthTotal(): string
    {
        $total = Penalty::whereMonth('penalty_date', Carbon::now()->month)
            ->whereYear('penalty_date', Carbon::now()->year)
            ->where('status', '!=', 'waived')
            ->sum('penalty_amount');

        return '₹' . number_format($total, 2);
    }

    private function getCustomerCharges(): string
    {
        $total = Penalty::whereMonth('penalty_date', Carbon::now()->month)
            ->whereYear('penalty_date', Carbon::now()->year)
            ->where('status', 'applied')
            ->sum('customer_amount');

        return '₹' . number_format($total, 2);
    }

    private function getAgencyAbsorption(): string
    {
        $total = Penalty::whereMonth('penalty_date', Carbon::now()->month)
            ->whereYear('penalty_date', Carbon::now()->year)
            ->where('status', '!=', 'waived')
            ->sum('agency_amount');

        return '₹' . number_format($total, 2);
    }
}
