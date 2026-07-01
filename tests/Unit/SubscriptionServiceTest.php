<?php

use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Enums\SubscriptionStatus;

it('lista las suscripciones que vencen dentro de N dias', function () {
    Subscription::factory()->create(['expires_at' => now()->addDays(2)]);  // dentro
    Subscription::factory()->create(['expires_at' => now()->addDays(10)]); // fuera
    Subscription::factory()->create(['expires_at' => now()->subDay()]);    // ya vencida

    $service = new SubscriptionService();
    expect($service->expiringWithin(3))->toHaveCount(1);
});

it('marca como vencidas las suscripciones cuya fecha paso', function () {
    Subscription::factory()->create(['expires_at' => now()->subDay()]);
    Subscription::factory()->create(['expires_at' => now()->addDay()]);

    $service = new SubscriptionService();
    $count = $service->markExpired();

    expect($count)->toBe(1)
        ->and(Subscription::where('status', SubscriptionStatus::Expired->value)->count())->toBe(1);
});
