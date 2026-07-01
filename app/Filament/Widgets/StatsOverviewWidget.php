<?php

namespace App\Filament\Widgets;

use App\Enums\ProfileStatus;
use App\Models\Customer;
use App\Models\Profile;
use App\Services\SubscriptionService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $expiringSoon = (new SubscriptionService())->expiringWithin(3)->count();
        $freeProfiles = Profile::where('status', ProfileStatus::Free->value)->count();

        return [
            Stat::make('Clientes', Customer::count()),
            Stat::make('Perfiles libres (stock)', $freeProfiles),
            Stat::make('Vencen en 3 días', $expiringSoon)
                ->color($expiringSoon > 0 ? 'warning' : 'success'),
        ];
    }
}
