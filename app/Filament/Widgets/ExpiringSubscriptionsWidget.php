<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ExpiringSubscriptionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Próximos vencimientos (7 días)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Subscription::query()
                    ->expiringWithin(7)
                    ->with(['customer', 'profile.account.platform'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')->label('Cliente'),
                Tables\Columns\TextColumn::make('profile.account.platform.name')->label('Plataforma'),
                Tables\Columns\TextColumn::make('expires_at')->label('Vence')->date(),
            ]);
    }
}
