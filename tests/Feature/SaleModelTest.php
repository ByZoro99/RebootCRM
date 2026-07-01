<?php

use App\Models\Sale;
use App\Models\Payment;
use App\Enums\PaymentStatus;

it('calcula el monto pagado y el saldo', function () {
    $sale = Sale::factory()->create(['total' => 100]);
    Payment::factory()->create(['sale_id' => $sale->id, 'amount' => 60, 'status' => PaymentStatus::Paid->value]);
    Payment::factory()->create(['sale_id' => $sale->id, 'amount' => 40, 'status' => PaymentStatus::Pending->value]);

    expect($sale->paidAmount())->toBe(60.0)
        ->and($sale->balance())->toBe(40.0);
});
