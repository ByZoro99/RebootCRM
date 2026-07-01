<?php

use App\Models\Account;
use App\Models\Platform;
use App\Enums\AccountStatus;
use Illuminate\Support\Facades\DB;

it('cifra la contraseña en la base de datos', function () {
    $account = Account::factory()->create(['password' => 'mi-clave-secreta']);

    // El accessor devuelve el texto plano
    expect($account->password)->toBe('mi-clave-secreta');

    // En la BD el valor está cifrado (distinto al texto plano)
    $raw = DB::table('accounts')->where('id', $account->id)->value('password');
    expect($raw)->not->toBe('mi-clave-secreta');
});

it('pertenece a una plataforma y castea el estado', function () {
    $platform = Platform::factory()->create();
    $account = Account::factory()->create([
        'platform_id' => $platform->id,
        'status' => AccountStatus::Active->value,
    ]);

    expect($account->platform->id)->toBe($platform->id)
        ->and($account->status)->toBe(AccountStatus::Active);
});
