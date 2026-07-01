<?php

use App\Models\Platform;

it('crea una plataforma con precio y perfiles', function () {
    $p = Platform::create([
        'name' => 'Netflix',
        'base_price' => 9.99,
        'profiles_per_account' => 5,
    ]);

    expect($p->name)->toBe('Netflix')
        ->and((float) $p->base_price)->toBe(9.99)
        ->and($p->profiles_per_account)->toBe(5)
        ->and($p->active)->toBeTrue();
});
