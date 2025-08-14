<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class ServiceProfitabilityTable extends BaseWidget
{
    protected static ?string $heading = 'Service Profitability Analysis';
    protected static ?string $description = 'Revenue vs Cost analysis for each service';
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = null;
    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Service::query()
                    ->join('categories', 'services.category_id', '=', 'categories.id')
                    ->leftJoin('invoice_service', function ($join) {
                        $join->on('services.id', '=', 'invoice_service.service_id');
                    })
                    ->leftJoin('invoices', function ($join) {
                        $join->on('invoice_service.invoice_id', '=', 'invoices.id')
                            ->where('invoices.status', '!=', 'cancelled')
                            ->where('invoices.status', '!=', 'refunded');
                    })
                    ->leftJoin('purchase_order_items', function ($join) {
                        $join->on('services.id', '=', 'purchase_order_items.service_id');
                    })
                    ->leftJoin('purchase_orders', function ($join) {
                        $join->on('purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                            ->where('purchase_orders.status', '!=', 'cancelled');
                    })
                    ->select([
                        'services.id',
                        'services.name',
                        'services.price',
                        'categories.name as category_name',
                        DB::raw('COALESCE(SUM(DISTINCT CASE WHEN invoices.id IS NOT NULL THEN invoice_service.quantity * invoice_service.unit_price ELSE 0 END), 0) as total_revenue'),
                        DB::raw('COALESCE(SUM(DISTINCT CASE WHEN purchase_orders.id IS NOT NULL THEN purchase_order_items.quantity * purchase_order_items.unit_price ELSE 0 END), 0) as total_cost'),
                        DB::raw('COUNT(DISTINCT invoice_service.id) as times_sold'),
                        DB::raw('COALESCE(SUM(DISTINCT CASE WHEN invoices.id IS NOT NULL THEN invoice_service.quantity * invoice_service.unit_price ELSE 0 END), 0) - COALESCE(SUM(DISTINCT CASE WHEN purchase_orders.id IS NOT NULL THEN purchase_order_items.quantity * purchase_order_items.unit_price ELSE 0 END), 0) as profit'),
                        DB::raw('CASE 
                            WHEN COALESCE(SUM(DISTINCT CASE WHEN invoices.id IS NOT NULL THEN invoice_service.quantity * invoice_service.unit_price ELSE 0 END), 0) > 0 
                            THEN ((COALESCE(SUM(DISTINCT CASE WHEN invoices.id IS NOT NULL THEN invoice_service.quantity * invoice_service.unit_price ELSE 0 END), 0) - COALESCE(SUM(DISTINCT CASE WHEN purchase_orders.id IS NOT NULL THEN purchase_order_items.quantity * purchase_order_items.unit_price ELSE 0 END), 0)) / COALESCE(SUM(DISTINCT CASE WHEN invoices.id IS NOT NULL THEN invoice_service.quantity * invoice_service.unit_price ELSE 0 END), 0)) * 100 
                            ELSE 0 
                        END as profit_margin')
                    ])
                    ->groupBy('services.id', 'services.name', 'services.price', 'categories.name')
                    ->havingRaw('COUNT(DISTINCT invoice_service.id) > 0')
                    ->orderByDesc('total_revenue')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Service Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category_name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Unit Price')
                    ->money('LKR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('times_sold')
                    ->label('Units Sold')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Total Revenue')
                    ->money('LKR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->money('LKR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('profit')
                    ->label('Gross Profit')
                    ->money('LKR')
                    ->color(function ($state) {
                        return $state >= 0 ? 'success' : 'danger';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('profit_margin')
                    ->label('Profit Margin')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 1) . '%';
                    })
                    ->badge()
                    ->color(function ($state) {
                        if ($state >= 25) return 'success';
                        if ($state >= 10) return 'warning';
                        return 'danger';
                    })
                    ->sortable(),
            ])
            ->defaultSort('total_revenue', 'desc')
            ->paginated([10, 25, 50]);
    }
}
