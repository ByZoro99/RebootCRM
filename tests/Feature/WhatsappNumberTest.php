<?php

use App\Models\WhatsappNumber;
use Illuminate\Support\Facades\DB;

it('cifra el token y devuelve el numero por defecto', function () {
    WhatsappNumber::factory()->create(['is_default' => false, 'label' => 'A']);
    $def = WhatsappNumber::factory()->create(['is_default' => true, 'label' => 'B', 'access_token' => 'secreto']);

    $raw = DB::table('whatsapp_numbers')->where('id', $def->id)->value('access_token');
    expect($raw)->not->toBe('secreto');
    expect($def->access_token)->toBe('secreto');

    expect(WhatsappNumber::default()->id)->toBe($def->id);
});
