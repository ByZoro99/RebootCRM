<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Support\Collection;

class SubscriptionService
{
    /** Suscripciones activas que vencen dentro de N días (incluye hoy). */
    public function expiringWithin(int $days): Collection
    {
        return Subscription::expiringWithin($days)->get();
    }

    /** Marca como vencidas las suscripciones activas cuya fecha ya pasó. */
    public function markExpired(): int
    {
        return Subscription::active()
            ->whereDate('expires_at', '<', now()->toDateString())
            ->update(['status' => \App\Enums\SubscriptionStatus::Expired->value]);
    }
}
