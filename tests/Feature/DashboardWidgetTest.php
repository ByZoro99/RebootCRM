<?php

use App\Models\Subscription;
use App\Services\SubscriptionService;

it('cuenta las suscripciones próximas a vencer para el dashboard', function () {
    Subscription::factory()->create(['expires_at' => now()->addDays(2)]);
    Subscription::factory()->create(['expires_at' => now()->addDays(30)]);

    expect((new SubscriptionService())->expiringWithin(3)->count())->toBe(1);
});
