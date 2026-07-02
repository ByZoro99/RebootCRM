<?php

use App\Models\WhatsappNumber;
use App\Models\WhatsappMessage;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    WhatsappNumber::factory()->create(['is_default' => true, 'phone_number_id' => '123', 'access_token' => 'tok']);
});

it('envia una plantilla y registra el mensaje como enviado', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.ABC']]], 200),
    ]);

    $service = new WhatsAppService();
    $msg = $service->sendTemplate('5215500000000', 'entrega_cuenta', ['Juan', 'Netflix']);

    expect($msg->status)->toBe('sent')
        ->and($msg->wa_message_id)->toBe('wamid.ABC')
        ->and(WhatsappMessage::count())->toBe(1);

    Http::assertSent(fn ($request) =>
        str_contains($request->url(), '/123/messages')
        && $request['type'] === 'template'
    );
});

it('registra el mensaje como fallido si Meta responde error', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['error' => ['message' => 'invalid']], 400),
    ]);

    $service = new WhatsAppService();
    $msg = $service->sendText('5215500000000', 'hola');

    expect($msg->status)->toBe('failed')
        ->and($msg->error)->toContain('invalid');
});
