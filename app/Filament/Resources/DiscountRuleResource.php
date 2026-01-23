<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountRuleResource\Pages;
use App\Models\DiscountRule;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DiscountRuleResource extends Resource
{
    protected static ?string $model = DiscountRule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-percent-badge';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_level')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->unique(ignoreRecord: true),
                TextInput::make('discount')
                    ->required()
                    ->numeric()
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_level')
                    ->label('User Level')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        1 => 'Bronze',
                        2 => 'Silver',
                        3 => 'Gold',
                        4 => 'Platinum',
                        5 => 'Diamond',
                        default => "Level {$state}",
                    })
                    ->sortable(),
                TextColumn::make('discount')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('user_level');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDiscountRules::route('/'),
            'create' => Pages\CreateDiscountRule::route('/create'),
            'edit' => Pages\EditDiscountRule::route('/{record}/edit'),
        ];
    }
}




