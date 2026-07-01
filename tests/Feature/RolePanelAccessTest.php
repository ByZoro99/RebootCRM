<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'vendedor', 'soporte'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    public function test_permite_acceso_al_panel_a_usuarios_con_rol(): void
    {
        $panel = filament()->getPanel('admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($admin->canAccessPanel($panel));
    }

    public function test_niega_acceso_a_usuarios_sin_rol(): void
    {
        $panel = filament()->getPanel('admin');

        $user = User::factory()->create();

        $this->assertFalse($user->canAccessPanel($panel));
    }
}
