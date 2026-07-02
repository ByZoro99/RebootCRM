<?php

use App\Models\Customer;
use App\Models\Subscription;
use App\Models\WhatsappNumber;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;

it('envia recordatorio y marca reminder_sent', function () {
    config(['whatsapp.reminder_days' => 3]);
    WhatsappNumber::factory()->create(['is_default' => true, 'phone_number_id' => '123', 'access_token' => 'tok']);
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.R']]], 200)]);

    $customer = Customer::factory()->create(['phone' => '5215500000000']);
    Subscription::factory()->create(['customer_id' => $customer->id, 'expires_at' => now()->addDays(2), 'reminder_sent' => false]);
    Subscription::factory()->create(['expires_at' => now()->addDays(30)]);

    $this->artisan('whatsapp:send-reminders')->assertSuccessful();

    expect(WhatsappMessage::where('status', 'sent')->count())->toBe(1)
        ->and(Subscription::where('customer_id', $customer->id)->first()->reminder_sent)->toBeTrue();
});

it('no reenvia si ya se envio el recordatorio', function () {
    WhatsappNumber::factory()->create(['is_default' => true]);
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

    $customer = Customer::factory()->create(['phone' => '5215500000000']);
    Subscription::factory()->create(['customer_id' => $customer->id, 'expires_at' => now()->addDays(2), 'reminder_sent' => true]);

    $this->artisan('whatsapp:send-reminders')->assertSuccessful();

    expect(WhatsappMessage::count())->toBe(0);
});
