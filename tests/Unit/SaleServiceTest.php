<?php

use App\Models\Customer;
use App\Models\Platform;
use App\Models\Account;
use App\Models\Profile;
use App\Services\SaleService;
use App\Enums\ProfileStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;

it('crea venta, pago, suscripcion y asigna el perfil', function () {
    $customer = Customer::factory()->create();
    $platform = Platform::factory()->create(['base_price' => 10]);
    $account = Account::factory()->create(['platform_id' => $platform->id]);
    $profile = Profile::factory()->create([
        'account_id' => $account->id,
        'status' => ProfileStatus::Free->value,
    ]);

    $service = new SaleService();
    $sale = $service->create(
        customer: $customer,
        profile: $profile,
        price: 10.0,
        months: 1,
        paymentMethod: 'efectivo',
    );

    // Venta y total
    expect((float) $sale->total)->toBe(10.0)
        ->and($sale->items)->toHaveCount(1);

    // Pago registrado y saldo cero
    expect($sale->paidAmount())->toBe(10.0)
        ->and($sale->balance())->toBe(0.0);

    // Perfil quedó asignado
    expect($profile->fresh()->status)->toBe(ProfileStatus::Assigned);

    // Suscripción creada, activa, vence en ~1 mes
    $sub = $customer->subscriptions()->first();
    expect($sub)->not->toBeNull()
        ->and($sub->status)->toBe(SubscriptionStatus::Active)
        ->and($sub->profile_id)->toBe($profile->id)
        ->and($sub->expires_at->toDateString())->toBe(now()->addMonth()->toDateString());
});

it('no permite vender un perfil ya asignado', function () {
    $profile = Profile::factory()->create(['status' => ProfileStatus::Assigned->value]);
    $customer = Customer::factory()->create();

    $service = new SaleService();
    $service->create($customer, $profile, 10.0, 1, 'efectivo');
})->throws(\RuntimeException::class);
