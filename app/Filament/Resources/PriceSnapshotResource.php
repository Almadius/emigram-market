<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PriceSnapshotResource\Pages;
use App\Models\PriceSnapshot;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PriceSnapshotResource extends Resource
{
    protected static ?string $model = PriceSnapshot::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shop_domain')
                    ->searchable(),
                TextColumn::make('product_url')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('price')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('source')
                    ->badge(),
                TextColumn::make('parsed_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('parsed_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPriceSnapshots::route('/'),
        ];
    }
}




