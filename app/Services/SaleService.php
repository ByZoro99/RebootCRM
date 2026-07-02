<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ProfileStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Profile;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function create(
        Customer $customer,
        Profile $profile,
        float $price,
        int $months,
        string $paymentMethod,
        bool $notify = true,
    ): Sale {
        if ($profile->status !== ProfileStatus::Free) {
            throw new \RuntimeException('El perfil no está disponible.');
        }

        $sale = DB::transaction(function () use ($customer, $profile, $price, $months, $paymentMethod) {
            $sale = Sale::create([
                'customer_id' => $customer->id,
                'seller_id' => $customer->seller_id,
                'total' => $price,
                'sold_at' => now(),
            ]);

            $platformId = $profile->account->platform_id;

            $sale->items()->create([
                'platform_id' => $platformId,
                'profile_id' => $profile->id,
                'description' => "Perfil {$profile->name} x{$months} mes(es)",
                'price' => $price,
                'months' => $months,
            ]);

            $sale->payments()->create([
                'amount' => $price,
                'method' => $paymentMethod,
                'status' => PaymentStatus::Paid->value,
                'paid_at' => now(),
            ]);

            $profile->update(['status' => ProfileStatus::Assigned->value]);

            $customer->subscriptions()->create([
                'profile_id' => $profile->id,
                'sale_id' => $sale->id,
                'starts_at' => now()->toDateString(),
                'expires_at' => now()->addMonths($months)->toDateString(),
                'status' => SubscriptionStatus::Active->value,
            ]);

            return $sale->load('items', 'payments');
        });

        if ($notify && $customer->phone) {
            $this->notifyDelivery($customer, $profile, $sale);
        }

        return $sale;
    }

    private function notifyDelivery(Customer $customer, Profile $profile, Sale $sale): void
    {
        try {
            $account = $profile->account()->with('platform')->first();
            $subscription = $sale->customer->subscriptions()->latest('id')->first();

            app(\App\Services\WhatsAppService::class)->sendTemplate(
                to: $customer->phone,
                template: config('whatsapp.templates.delivery'),
                params: [
                    $customer->name,
                    $account->platform->name ?? 'streaming',
                    $account->email,
                    $account->password,
                    $profile->name,
                    optional($subscription)->expires_at?->format('d/m/Y') ?? '',
                ],
                customer: $customer,
            );
        } catch (\Throwable $e) {
            report($e); // best-effort: no rompe la venta
        }
    }
}
