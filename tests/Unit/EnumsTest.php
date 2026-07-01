<?php

use App\Enums\AccountStatus;
use App\Enums\ProfileStatus;
use App\Enums\SubscriptionStatus;

it('tiene los valores esperados de estado de cuenta', function () {
    expect(AccountStatus::Active->value)->toBe('active')
        ->and(AccountStatus::Expired->value)->toBe('expired')
        ->and(AccountStatus::Blocked->value)->toBe('blocked');
});

it('distingue perfil libre y asignado', function () {
    expect(ProfileStatus::Free->value)->toBe('free')
        ->and(ProfileStatus::Assigned->value)->toBe('assigned');
});

it('tiene estado activo y vencido de suscripcion', function () {
    expect(SubscriptionStatus::Active->value)->toBe('active')
        ->and(SubscriptionStatus::Expired->value)->toBe('expired');
});
