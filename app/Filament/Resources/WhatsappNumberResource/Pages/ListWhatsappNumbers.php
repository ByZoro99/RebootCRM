<?php

namespace App\Filament\Resources\WhatsappNumberResource\Pages;

use App\Filament\Resources\WhatsappNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWhatsappNumbers extends ListRecords
{
    protected static string $resource = WhatsappNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
