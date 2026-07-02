<?php

use App\Models\Customer;
use App\Models\Platform;
use App\Models\Account;
use App\Models\Profile;
use App\Models\WhatsappNumber;
use App\Models\WhatsappMessage;
use App\Services\SaleService;
use App\Enums\ProfileStatus;
use Illuminate\Support\Facades\Http;

it('envia los datos de la cuenta por WhatsApp al vender', function () {
    WhatsappNumber::factory()->create(['is_default' => true, 'phone_number_id' => '123', 'access_token' => 'tok']);
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);

    $customer = Customer::factory()->create(['phone' => '5215500000000']);
    $platform = Platform::factory()->create();
    $account = Account::factory()->create(['platform_id' => $platform->id]);
    $profile = Profile::factory()->create(['account_id' => $account->id, 'status' => ProfileStatus::Free->value]);

    (new SaleService())->create($customer, $profile, 10.0, 1, 'efectivo');

    expect(WhatsappMessage::where('customer_id', $customer->id)->where('status', 'sent')->count())->toBe(1);
    Http::assertSent(fn ($r) => $r['type'] === 'template');
});

it('no rompe la venta si no hay whatsapp configurado', function () {
    $customer = Customer::factory()->create(['phone' => '5215500000000']);
    $platform = Platform::factory()->create();
    $account = Account::factory()->create(['platform_id' => $platform->id]);
    $profile = Profile::factory()->create(['account_id' => $account->id, 'status' => ProfileStatus::Free->value]);

    $sale = (new SaleService())->create($customer, $profile, 10.0, 1, 'efectivo');

    expect($sale->exists)->toBeTrue()
        ->and($profile->fresh()->status)->toBe(ProfileStatus::Assigned);
});
