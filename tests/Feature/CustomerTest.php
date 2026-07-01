<?php

use App\Models\Customer;
use App\Models\User;

it('crea un cliente y lo asocia a un vendedor', function () {
    $seller = User::factory()->create();
    $customer = Customer::factory()->create(['seller_id' => $seller->id]);

    expect($customer->seller->id)->toBe($seller->id)
        ->and($customer->name)->not->toBeEmpty();
});
