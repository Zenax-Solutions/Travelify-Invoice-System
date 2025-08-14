<?php

namespace App\Filament\Widgets;

use App\Models\Penalty;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PenaltyReissueOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static ?int $sort = 8;

    protected function getStats(): array
    {
        // Get invoice re-issue statistics
        $totalRequiringReissue = Penalty::where('requires_invoice_reissue', true)
            ->where('status', 'applied')
            ->count();

        $pendingReissue = Penalty::where('requires_invoice_reissue', true)
            ->where('invoice_reissued', false)
            ->where('status', 'applied')
            ->count();

        $completedReissue = Penalty::where('requires_invoice_reissue', true)
            ->where('invoice_reissued', true)
            ->where('status', 'applied')
            ->count();

        $highPriorityPending = Penalty::where('requires_invoice_reissue', true)
            ->where('invoice_reissued', false)
            ->where('status', 'applied')
            ->where('reissue_priority', 'high')
            ->count();

        $mediumPriorityPending = Penalty::where('requires_invoice_reissue', true)
            ->where('invoice_reissued', false)
            ->where('status', 'applied')
            ->where('reissue_priority', 'medium')
            ->count();

        // Calculate completion rate
        $completionRate = $totalRequiringReissue > 0
            ? round(($completedReissue / $totalRequiringReissue) * 100, 1)
            : 0;

        return [
            Stat::make('Pending Invoice Re-issues', $pendingReissue)
                ->description($pendingReissue > 0 ? 'Invoices requiring re-issue' : 'All invoices up to date')
                ->descriptionIcon($pendingReissue > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($pendingReissue > 0 ? 'warning' : 'success')
                ->chart($this->getPendingReissueChart()),

            Stat::make('High Priority Pending', $highPriorityPending)
                ->description($highPriorityPending > 0 ? 'Urgent re-issues needed' : 'No urgent re-issues')
                ->descriptionIcon($highPriorityPending > 0 ? 'heroicon-m-fire' : 'heroicon-m-check-circle')
                ->color($highPriorityPending > 0 ? 'danger' : 'success')
                ->chart($this->getPriorityBreakdownChart()),

            Stat::make('Completion Rate', $completionRate . '%')
                ->description("Completed: {$completedReissue} of {$totalRequiringReissue}")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($completionRate >= 80 ? 'success' : ($completionRate >= 60 ? 'warning' : 'danger'))
                ->chart($this->getCompletionTrendChart()),
        ];
    }

    private function getPendingReissueChart(): array
    {
        // Get pending re-issues for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Penalty::where('requires_invoice_reissue', true)
                ->where('invoice_reissued', false)
                ->where('status', 'applied')
                ->whereDate('created_at', '<=', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    private function getPriorityBreakdownChart(): array
    {
        // Get priority distribution for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $highPriority = Penalty::where('requires_invoice_reissue', true)
                ->where('invoice_reissued', false)
                ->where('status', 'applied')
                ->where('reissue_priority', 'high')
                ->whereDate('created_at', '<=', $date)
                ->count();
            $data[] = $highPriority;
        }
        return $data;
    }

    private function getCompletionTrendChart(): array
    {
        // Get completion rate trend for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $total = Penalty::where('requires_invoice_reissue', true)
                ->where('status', 'applied')
                ->whereDate('created_at', '<=', $date)
                ->count();
            $completed = Penalty::where('requires_invoice_reissue', true)
                ->where('invoice_reissued', true)
                ->where('status', 'applied')
                ->whereDate('created_at', '<=', $date)
                ->count();

            $rate = $total > 0 ? round(($completed / $total) * 100) : 0;
            $data[] = $rate;
        }
        return $data;
    }
}
