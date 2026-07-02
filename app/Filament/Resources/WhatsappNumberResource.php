<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsappNumberResource\Pages;
use App\Filament\Resources\WhatsappNumberResource\RelationManagers;
use App\Models\WhatsappNumber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WhatsappNumberResource extends Resource
{
    protected static ?string $model = WhatsappNumber::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('label')->required(),
                Forms\Components\TextInput::make('phone_number_id')->required()->label('Phone Number ID'),
                Forms\Components\TextInput::make('display_number')->label('Número visible'),
                Forms\Components\TextInput::make('access_token')->password()->revealable()->required()->label('Access token'),
                Forms\Components\Toggle::make('is_default')->label('Por defecto'),
                Forms\Components\Toggle::make('active')->default(true)->label('Activo'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')->searchable(),
                Tables\Columns\TextColumn::make('phone_number_id')->label('Phone Number ID')->searchable(),
                Tables\Columns\TextColumn::make('display_number')->label('Número visible'),
                Tables\Columns\IconColumn::make('is_default')->boolean()->label('Por defecto'),
                Tables\Columns\IconColumn::make('active')->boolean()->label('Activo'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsappNumbers::route('/'),
            'create' => Pages\CreateWhatsappNumber::route('/create'),
            'edit' => Pages\EditWhatsappNumber::route('/{record}/edit'),
        ];
    }
}
