<?php

use App\Models\User;
use App\Filament\Resources\PlatformResource;
use App\Filament\Resources\CustomerResource;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    foreach (['admin', 'vendedor', 'soporte'] as $r) {
        Role::firstOrCreate(['name' => $r]);
    }
});

it('solo el admin ve el recurso de plataformas', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $vendedor = User::factory()->create();
    $vendedor->assignRole('vendedor');

    actingAs($admin);
    expect(PlatformResource::canAccess())->toBeTrue();

    actingAs($vendedor);
    expect(PlatformResource::canAccess())->toBeFalse();
});

it('vendedor puede acceder a clientes', function () {
    $vendedor = User::factory()->create();
    $vendedor->assignRole('vendedor');

    actingAs($vendedor);
    expect(CustomerResource::canAccess())->toBeTrue();
});
