# RebootCRM — Fase 1 (MVP) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construir el MVP del CRM en Laravel 11 + Filament 3: usuarios con roles (Admin/Vendedor/Soporte), clientes, inventario de cuentas/perfiles, ventas/pagos y suscripciones con vencimiento, instalable como PWA. Sin WhatsApp todavía (Fase 2).

**Architecture:** App web monolítica Laravel. Panel administrativo generado con Filament 3 (Livewire/Blade). Lógica de negocio en Models + clases de servicio (`app/Services`). Permisos por rol con spatie/laravel-permission. Base de datos MariaDB (XAMPP). PWA vía manifest + service worker.

**Tech Stack:** Laravel 11, PHP 8.2 (XAMPP), MariaDB 10.4, Filament 3, spatie/laravel-permission, Pest (tests), Vite/npm.

---

## Notas de entorno (Windows + XAMPP)

- **PHP:** `C:\xampp\php\php.exe` (no está en PATH). Para Artisan usar:
  `& "C:\xampp\php\php.exe" artisan <cmd>` desde `c:\xampp\htdocs\CRM`.
- **MySQL/MariaDB:** `C:\xampp\mysql\bin\mysql.exe`. Arrancar MySQL desde el panel de XAMPP antes de migrar.
- **Composer:** se instala en Task 0.
- **Carpeta del proyecto:** `c:\xampp\htdocs\CRM` (ya tiene git, `CONTEXT.md`, `CHANGELOG.md`, `docs/`). El scaffold de Laravel se fusiona preservando estos archivos.
- **Todos los comandos** se ejecutan desde `c:\xampp\htdocs\CRM` salvo que se indique.
- Tras cada Task con cambios relevantes: **commit + push** (la rama `main` ya rastrea `origin`).

### Atajo recomendado
Definir una variable para PHP al inicio de cada sesión PowerShell:
```powershell
$php = "C:\xampp\php\php.exe"
& $php artisan --version
```
En el resto del plan se escribe `php artisan ...`; sustituir `php` por `& $php`.

---

## Estructura de archivos (Fase 1)

```
CRM/
├── app/
│   ├── Models/
│   │   ├── User.php            (modificado: HasRoles, canAccessPanel)
│   │   ├── Platform.php
│   │   ├── Account.php         (password cifrada via cast)
│   │   ├── Profile.php
│   │   ├── Customer.php
│   │   ├── Sale.php
│   │   ├── SaleItem.php
│   │   ├── Payment.php
│   │   └── Subscription.php
│   ├── Services/
│   │   ├── SaleService.php     (crear venta: items, pago, suscripción, asignar perfil)
│   │   └── SubscriptionService.php (estado/vencimientos)
│   ├── Enums/
│   │   ├── AccountStatus.php
│   │   ├── ProfileStatus.php
│   │   ├── PaymentStatus.php
│   │   └── SubscriptionStatus.php
│   ├── Filament/
│   │   └── Resources/          (Platform, Account, Customer, Sale, Subscription, User)
│   └── Providers/Filament/AdminPanelProvider.php
├── database/
│   ├── migrations/             (una por tabla)
│   └── seeders/
│       ├── RoleSeeder.php
│       └── DatabaseSeeder.php  (admin inicial)
├── tests/
│   ├── Feature/                (permisos, panel, recursos)
│   └── Unit/                   (servicios, modelos)
├── public/
│   ├── manifest.json           (PWA)
│   ├── sw.js                   (service worker)
│   └── icons/                  (íconos PWA)
└── resources/views/...
```

---

## Task 0: Instalar Composer y scaffolding de Laravel

**Files:**
- Create: todo el esqueleto de Laravel dentro de `c:\xampp\htdocs\CRM`
- Modify: `.gitignore` (fusionar con el de Laravel), `.env`

- [ ] **Step 1: Instalar Composer (si falta)**

Descargar e instalar el instalador oficial de Windows desde https://getcomposer.org/Composer-Setup.exe
(usa el PHP de XAMPP: `C:\xampp\php\php.exe`). Tras instalar, abrir una **nueva** terminal y verificar:

Run: `composer --version`
Expected: `Composer version 2.x.x`

> Alternativa por línea de comandos (PowerShell, sin navegador):
> ```powershell
> $php = "C:\xampp\php\php.exe"
> & $php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
> & $php composer-setup.php --install-dir="C:\xampp\php" --filename=composer
> Remove-Item composer-setup.php
> ```
> Luego usar `& $php C:\xampp\php\composer ...` o agregar `C:\xampp\php` al PATH.

- [ ] **Step 2: Habilitar extensiones PHP necesarias**

Editar `C:\xampp\php\php.ini` y asegurar que estén **sin** `;` al inicio:
`extension=pdo_mysql`, `extension=mbstring`, `extension=openssl`, `extension=fileinfo`,
`extension=gd`, `extension=curl`, `extension=zip`, `extension=intl`.

Run: `& "C:\xampp\php\php.exe" -m`
Expected: la lista incluye `pdo_mysql`, `mbstring`, `openssl`, `intl`, `curl`, `zip`.

- [ ] **Step 3: Crear el proyecto Laravel en carpeta temporal**

Laravel requiere carpeta vacía; la nuestra ya tiene git/docs. Creamos en temp y fusionamos:

```powershell
cd c:\xampp\htdocs
composer create-project laravel/laravel CRM_laravel_tmp "11.*"
```
Expected: termina con "Application ready!".

- [ ] **Step 4: Fusionar el scaffold en la carpeta CRM (preservando git/docs)**

```powershell
$src = "c:\xampp\htdocs\CRM_laravel_tmp"
$dst = "c:\xampp\htdocs\CRM"
# Renombrar el .gitignore de Laravel para fusionarlo a mano luego
Rename-Item "$src\.gitignore" ".gitignore.laravel"
# Copiar todo el contenido (incluye ocultos) sin sobreescribir .git, CONTEXT.md, etc.
Get-ChildItem -Path $src -Force | ForEach-Object {
    Copy-Item $_.FullName -Destination $dst -Recurse -Force
}
Remove-Item $src -Recurse -Force
```

- [ ] **Step 5: Fusionar .gitignore**

Añadir al final de `c:\xampp\htdocs\CRM\.gitignore` el contenido relevante de Laravel
(`.gitignore.laravel`): `/vendor`, `/node_modules`, `/public/build`, `/public/hot`,
`/storage/*.key`, `.env`, `.env.backup`, `.phpunit.result.cache`, `Homestead.*`,
`/.fleet`, `/.idea`, `/.vscode`. Luego borrar `.gitignore.laravel`. Evitar duplicados
(ya teníamos `/vendor/`, `.env`, etc.).

- [ ] **Step 6: Crear la base de datos en MariaDB**

Arrancar MySQL en el panel de XAMPP. Luego:
```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE rebootcrm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```
Expected: sin error (prompt vuelve).

- [ ] **Step 7: Configurar `.env`**

En `c:\xampp\htdocs\CRM\.env` poner:
```env
APP_NAME=RebootCRM
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rebootcrm
DB_USERNAME=root
DB_PASSWORD=
```

- [ ] **Step 8: Generar key y migrar**

```powershell
$php = "C:\xampp\php\php.exe"
& $php artisan key:generate
& $php artisan migrate
```
Expected: `migrate` crea las tablas por defecto (users, cache, jobs) sin error.

- [ ] **Step 9: Verificar que la app levanta**

Run: `& "C:\xampp\php\php.exe" artisan serve`
Expected: "Server running on http://127.0.0.1:8000". Abrir en navegador → pantalla de bienvenida de Laravel. Detener con Ctrl+C.

- [ ] **Step 10: Commit**

```powershell
git add -A
git commit -m "chore: scaffold Laravel 11 en RebootCRM"
git push
```

---

## Task 1: Instalar y configurar Filament 3

**Files:**
- Create: `app/Providers/Filament/AdminPanelProvider.php` (generado), recursos de Filament
- Modify: `composer.json`

- [ ] **Step 1: Instalar Filament**

```powershell
$php = "C:\xampp\php\php.exe"
& $php C:\xampp\php\composer require filament/filament:"^3.2" -W
& $php artisan filament:install --panels
```
Expected: crea `app/Providers/Filament/AdminPanelProvider.php` y registra el panel en `/admin`.

- [ ] **Step 2: Crear usuario administrador de Filament**

```powershell
& "C:\xampp\php\php.exe" artisan make:filament-user
```
Ingresar: nombre `Admin`, email `admin@rebootcrm.test`, password a elección.
Expected: "Success!".

- [ ] **Step 3: Verificar acceso al panel**

Run: `& "C:\xampp\php\php.exe" artisan serve` y abrir `http://localhost:8000/admin`
Expected: login de Filament; al entrar, dashboard vacío. Detener servidor.

- [ ] **Step 4: Commit**

```powershell
git add -A
git commit -m "feat: instalar panel Filament 3 (/admin)"
git push
```

---

## Task 2: Roles y permisos (Admin / Vendedor / Soporte)

**Files:**
- Create: `database/seeders/RoleSeeder.php`, `tests/Feature/RolePanelAccessTest.php`
- Modify: `app/Models/User.php`, `database/seeders/DatabaseSeeder.php`, `composer.json`

- [ ] **Step 1: Instalar spatie/laravel-permission**

```powershell
$php = "C:\xampp\php\php.exe"
& $php C:\xampp\php\composer require spatie/laravel-permission
& $php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
& $php artisan migrate
```
Expected: crea tablas `roles`, `permissions`, etc.

- [ ] **Step 2: Añadir HasRoles y control de acceso al panel en User**

En `app/Models/User.php`, añadir el trait y la interfaz de Filament:
```php
<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use Notifiable, HasRoles;

    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['admin', 'vendedor', 'soporte']);
    }
}
```

- [ ] **Step 3: Crear el RoleSeeder**

`database/seeders/RoleSeeder.php`:
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'vendedor', 'soporte'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}
```

- [ ] **Step 4: Asignar rol admin al usuario inicial en DatabaseSeeder**

En `database/seeders/DatabaseSeeder.php`:
```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@rebootcrm.test'],
            ['name' => 'Admin', 'password' => Hash::make('password')]
        );
        $admin->assignRole('admin');
    }
}
```

- [ ] **Step 5: Escribir el test de acceso por rol**

`tests/Feature/RolePanelAccessTest.php`:
```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'vendedor', 'soporte'] as $r) {
        Role::firstOrCreate(['name' => $r]);
    }
});

it('permite acceso al panel a usuarios con rol', function () {
    $panel = filament()->getPanel('admin');

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    expect($admin->canAccessPanel($panel))->toBeTrue();
});

it('niega acceso a usuarios sin rol', function () {
    $panel = filament()->getPanel('admin');
    $user = User::factory()->create();
    expect($user->canAccessPanel($panel))->toBeFalse();
});
```

- [ ] **Step 6: Ejecutar el test (debe fallar si algo está mal, pasar si bien)**

Run: `& "C:\xampp\php\php.exe" artisan test --filter=RolePanelAccessTest`
Expected: 2 passed.

> Nota: configurar `phpunit.xml` para usar SQLite en memoria en tests:
> `<env name="DB_CONNECTION" value="sqlite"/>` y `<env name="DB_DATABASE" value=":memory:"/>`.
> Requiere `extension=pdo_sqlite` habilitada en `php.ini`.

- [ ] **Step 7: Ejecutar seeders en la BD real**

```powershell
& "C:\xampp\php\php.exe" artisan db:seed
```
Expected: roles creados y admin con rol asignado.

- [ ] **Step 8: Commit**

```powershell
git add -A
git commit -m "feat: roles Admin/Vendedor/Soporte con spatie + acceso al panel"
git push
```

---

## Task 3: Enum de estados (base reutilizable)

**Files:**
- Create: `app/Enums/AccountStatus.php`, `ProfileStatus.php`, `PaymentStatus.php`, `SubscriptionStatus.php`
- Test: `tests/Unit/EnumsTest.php`

- [ ] **Step 1: Escribir el test de los enums**

`tests/Unit/EnumsTest.php`:
```php
<?php

use App\Enums\AccountStatus;
use App\Enums\ProfileStatus;
use App\Enums\SubscriptionStatus;

it('tiene los valores esperados de estado de cuenta', function () {
    expect(AccountStatus::Active->value)->toBe('active')
        ->and(AccountStatus::Expired->value)->toBe('expired')
        ->and(AccountStatus::Blocked->value)->toBe('blocked');
});

it('distingue perfil libre y asignado', function () {
    expect(ProfileStatus::Free->value)->toBe('free')
        ->and(ProfileStatus::Assigned->value)->toBe('assigned');
});

it('tiene estado activo y vencido de suscripcion', function () {
    expect(SubscriptionStatus::Active->value)->toBe('active')
        ->and(SubscriptionStatus::Expired->value)->toBe('expired');
});
```

- [ ] **Step 2: Ejecutar el test (debe fallar)**

Run: `& "C:\xampp\php\php.exe" artisan test --filter=EnumsTest`
Expected: FAIL ("Class App\Enums\AccountStatus not found").

- [ ] **Step 3: Crear los enums**

`app/Enums/AccountStatus.php`:
```php
<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Expired => 'Vencida',
            self::Blocked => 'Bloqueada',
        };
    }
}
```

`app/Enums/ProfileStatus.php`:
```php
<?php

namespace App\Enums;

enum ProfileStatus: string
{
    case Free = 'free';
    case Assigned = 'assigned';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Libre',
            self::Assigned => 'Asignado',
        };
    }
}
```

`app/Enums/PaymentStatus.php`:
```php
<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Paid = 'paid';
    case Pending = 'pending';
    case Debt = 'debt';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Pagado',
            self::Pending => 'Pendiente',
            self::Debt => 'Deuda',
        };
    }
}
```

`app/Enums/SubscriptionStatus.php`:
```php
<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Expired => 'Vencida',
            self::Cancelled => 'Cancelada',
        };
    }
}
```

- [ ] **Step 4: Ejecutar el test (debe pasar)**

Run: `& "C:\xampp\php\php.exe" artisan test --filter=EnumsTest`
Expected: 3 passed.

- [ ] **Step 5: Commit**

```powershell
git add -A
git commit -m "feat: enums de estados (cuenta, perfil, pago, suscripcion)"
git push
```

---

## Task 4: Modelo Platform (plataformas de streaming)

**Files:**
- Create: `app/Models/Platform.php`, migración, `database/factories/PlatformFactory.php`
- Test: `tests/Feature/PlatformTest.php`

- [ ] **Step 1: Generar migración y modelo**

```powershell
& "C:\xampp\php\php.exe" artisan make:model Platform -mf
```

- [ ] **Step 2: Definir la migración**

En la migración `..._create_platforms_table.php`, método `up()`:
```php
Schema::create('platforms', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->decimal('base_price', 10, 2)->default(0);
    $table->unsignedSmallInteger('profiles_per_account')->default(1);
    $table->boolean('active')->default(true);
    $table->timestamps();
});
```

- [ ] **Step 3: Definir el modelo**

`app/Models/Platform.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'base_price', 'profiles_per_account', 'active'];

    protected $casts = [
        'base_price' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }
}
```

- [ ] **Step 4: Definir la factory**

`database/factories/PlatformFactory.php` método `definition()`:
```php
return [
    'name' => fake()->randomElement(['Netflix', 'Disney+', 'Spotify', 'HBO Max']),
    'base_price' => fake()->randomFloat(2, 2, 15),
    'profiles_per_account' => fake()->numberBetween(1, 5),
    'active' => true,
];
```

- [ ] **Step 5: Escribir el test**

`tests/Feature/PlatformTest.php`:
```php
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
```

- [ ] **Step 6: Ejecutar el test**

Run: `& "C:\xampp\php\php.exe" artisan test --filter=PlatformTest`
Expected: 1 passed.

- [ ] **Step 7: Crear el recurso Filament**

```powershell
& "C:\xampp\php\php.exe" artisan make:filament-resource Platform --generate
```
En `app/Filament/Resources/PlatformResource.php`, asegurar el formulario con los campos:
```php
use Filament\Forms;
// dentro de form():
Forms\Components\TextInput::make('name')->required()->maxLength(255),
Forms\Components\TextInput::make('base_price')->numeric()->prefix('$')->required(),
Forms\Components\TextInput::make('profiles_per_account')->numeric()->minValue(1)->default(1),
Forms\Components\Toggle::make('active')->default(true),
```

- [ ] **Step 8: Verificar en el panel**

Levantar `artisan serve`, ir a `/admin`, sección Platforms → crear una plataforma de prueba. Detener servidor.

- [ ] **Step 9: Commit**

```powershell
git add -A
git commit -m "feat: modelo y recurso Platform"
git push
```

---

## Task 5: Modelo Account (inventario, password cifrada)

**Files:**
- Create: `app/Models/Account.php`, migración, factory
- Test: `tests/Feature/AccountTest.php`

- [ ] **Step 1: Generar modelo/migración/factory**

```powershell
& "C:\xampp\php\php.exe" artisan make:model Account -mf
```

- [ ] **Step 2: Migración**

```php
Schema::create('accounts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('platform_id')->constrained()->cascadeOnDelete();
    $table->string('email');
    $table->text('password'); // cifrada por el cast 'encrypted'
    $table->unsignedSmallInteger('profiles_total')->default(1);
    $table->string('status')->default('active'); // AccountStatus
    $table->date('purchased_at')->nullable();
    $table->decimal('cost', 10, 2)->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 3: Modelo con cast de cifrado**

`app/Models/Account.php`:
```php
<?php

namespace App\Models;

use App\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_id', 'email', 'password', 'profiles_total',
        'status', 'purchased_at', 'cost', 'notes',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'status' => AccountStatus::class,
        'purchased_at' => 'date',
        'cost' => 'decimal:2',
    ];

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }
}
```

- [ ] **Step 4: Factory**

`database/factories/AccountFactory.php` `definition()`:
```php
return [
    'platform_id' => \App\Models\Platform::factory(),
    'email' => fake()->unique()->safeEmail(),
    'password' => 'secret123',
    'profiles_total' => 5,
    'status' => \App\Enums\AccountStatus::Active->value,
    'purchased_at' => now(),
    'cost' => fake()->randomFloat(2, 1, 10),
];
```

- [ ] **Step 5: Escribir el test (cifrado + relación)**

`tests/Feature/AccountTest.php`:
```php
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
```

- [ ] **Step 6: Ejecutar el test**

Run: `& "C:\xampp\php\php.exe" artisan test --filter=AccountTest`
Expected: 2 passed.

- [ ] **Step 7: Recurso Filament (ocultar password)**

```powershell
& "C:\xampp\php\php.exe" artisan make:filament-resource Account --generate
```
Ajustar el form en `AccountResource.php`:
```php
Forms\Components\Select::make('platform_id')->relationship('platform', 'name')->required(),
Forms\Components\TextInput::make('email')->email()->required(),
Forms\Components\TextInput::make('password')->password()->revealable()->required(),
Forms\Components\TextInput::make('profiles_total')->numeric()->minValue(1)->default(1),
Forms\Components\Select::make('status')
    ->options(collect(\App\Enums\AccountStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
    ->default('active')->required(),
Forms\Components\DatePicker::make('purchased_at'),
Forms\Components\TextInput::make('cost')->numeric()->prefix('$'),
Forms\Components\Textarea::make('notes')->columnSpanFull(),
```
En la tabla, NO mostrar la columna `password`.

- [ ] **Step 8: Commit**

```powershell
git add -A
git commit -m "feat: modelo Account con password cifrada + recurso"
git push
```

---

## Task 6: Modelo Profile (perfiles dentro de la cuenta)

**Files:**
- Create: `app/Models/Profile.php`, migración, factory
- Test: `tests/Feature/ProfileTest.php`

- [ ] **Step 1: Generar**

```powershell
& "C:\xampp\php\php.exe" artisan make:model Profile -mf
```

- [ ] **Step 2: Migración**

```php
Schema::create('profiles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('account_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('pin', 8)->nullable();
    $table->string('status')->default('free'); // ProfileStatus
    $table->timestamps();
});
```

- [ ] **Step 3: Modelo**

`app/Models/Profile.php`:
```php
<?php

namespace App\Models;

use App\Enums\ProfileStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = ['account_id', 'name', 'pin', 'status'];

    protected $casts = ['status' => ProfileStatus::class];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->whereNull('cancelled_at');
    }

    public function isFree(): bool
    {
        return $this->status === ProfileStatus::Free;
    }

    public function scopeFree($query)
    {
        return $query->where('status', ProfileStatus::Free->value);
    }
}
```

- [ ] **Step 4: Factory**

`database/factories/ProfileFactory.php` `definition()`:
```php
return [
    'account_id' => \App\Models\Account::factory(),
    'name' => fake()->firstName(),
    'pin' => (string) fake()->numberBetween(1000, 9999),
    'status' => \App\Enums\ProfileStatus::Free->value,
];
```

- [ ] **Step 5: Test**

`tests/Feature/ProfileTest.php`:
```php
<?php

use App\Models\Profile;
use App\Enums\ProfileStatus;

it('marca un perfil como libre por defecto', function () {
    $profile = Profile::factory()->create();
    expect($profile->isFree())->toBeTrue()
        ->and($profile->status)->toBe(ProfileStatus::Free);
});

it('el scope free solo devuelve perfiles libres', function () {
    Profile::factory()->create(['status' => ProfileStatus::Free->value]);
    Profile::factory()->create(['status' => ProfileStatus::Assigned->value]);

    expect(Profile::free()->count())->toBe(1);
});
```

- [ ] **Step 6: Ejecutar el test**

Run: `& "C:\xampp\php\php.exe" artisan test --filter=ProfileTest`
Expected: 2 passed.

- [ ] **Step 7: Gestionar perfiles desde la cuenta (relation manager)**

```powershell
& "C:\xampp\php\php.exe" artisan make:filament-relation-manager AccountResource profiles name
```
Registrar el `ProfilesRelationManager` en `AccountResource::getRelations()` para crear perfiles dentro de cada cuenta. Form del relation manager:
```php
Forms\Components\TextInput::make('name')->required(),
Forms\Components\TextInput::make('pin')->maxLength(8),
Forms\Components\Select::make('status')
    ->options(collect(\App\Enums\ProfileStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
    ->default('free'),
```

- [ ] **Step 8: Commit**

```powershell
git add -A
git commit -m "feat: modelo Profile + gestion desde la cuenta"
git push
```

---

## Task 7: Modelo Customer (clientes)

**Files:**
- Create: `app/Models/Customer.php`, migración, factory
- Test: `tests/Feature/CustomerTest.php`

- [ ] **Step 1: Generar**

```powershell
& "C:\xampp\php\php.exe" artisan make:model Customer -mf
```

- [ ] **Step 2: Migración**

```php
Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('phone')->nullable();      // WhatsApp (formato internacional)
    $table->string('email')->nullable();
    $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 3: Modelo**

`app/Models/Customer.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'email', 'seller_id', 'notes'];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
```

- [ ] **Step 4: Factory**

`database/factories/CustomerFactory.php` `definition()`:
```php
return [
    'name' => fake()->name(),
    'phone' => '521' . fake()->numerify('##########'),
    'email' => fake()->optional()->safeEmail(),
    'seller_id' => null,
    'notes' => null,
];
```

- [ ] **Step 5: Test**

`tests/Feature/CustomerTest.php`:
```php
<?php

use App\Models\Customer;
use App\Models\User;

it('crea un cliente y lo asocia a un vendedor', function () {
    $seller = User::factory()->create();
    $customer = Customer::factory()->create(['seller_id' => $seller->id]);

    expect($customer->seller->id)->toBe($seller->id)
        ->and($customer->sales)->toHaveCount(0);
});
```

- [ ] **Step 6: Ejecutar el test**

Run: `& "C:\xampp\php\php.exe" artisan test --filter=CustomerTest`
Expected: 1 passed.

- [ ] **Step 7: Recurso Filament**

```powershell
& "C:\xampp\php\php.exe" artisan make:filament-resource Customer --generate
```
Form:
```php
Forms\Components\TextInput::make('name')->required(),
Forms\Components\TextInput::make('phone')->tel()->helperText('Formato internacional, ej. 5215512345678'),
Forms\Components\TextInput::make('email')->email(),
Forms\Components\Select::make('seller_id')->relationship('seller', 'name')->label('Vendedor'),
Forms\Components\Textarea::make('notes')->columnSpanFull(),
```

- [ ] **Step 8: Commit**

```powershell
git add -A
git commit -m "feat: modelo y recurso Customer"
git push
```

---

## Task 8: Ventas, items y pagos (modelos + migraciones)

**Files:**
- Create: `app/Models/Sale.php`, `SaleItem.php`, `Payment.php`, 3 migraciones, factories
- Test: `tests/Feature/SaleModelTest.php`

- [ ] **Step 1: Generar**

```powershell
& "C:\xampp\php\php.exe" artisan make:model Sale -mf
& "C:\xampp\php\php.exe" artisan make:model SaleItem -mf
& "C:\xampp\php\php.exe" artisan make:model Payment -mf
```

- [ ] **Step 2: Migraciones**

`sales`:
```php
Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
    $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
    $table->decimal('total', 10, 2)->default(0);
    $table->timestamp('sold_at')->useCurrent();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```
`sale_items`:
```php
Schema::create('sale_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
    $table->foreignId('platform_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('profile_id')->nullable()->constrained()->nullOnDelete();
    $table->string('description');
    $table->decimal('price', 10, 2)->default(0);
    $table->unsignedSmallInteger('months')->default(1);
    $table->timestamps();
});
```
`payments`:
```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
    $table->decimal('amount', 10, 2);
    $table->string('method')->nullable();      // efectivo, transferencia, etc.
    $table->string('status')->default('paid'); // PaymentStatus
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 3: Modelos**

`app/Models/Sale.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'seller_id', 'total', 'sold_at', 'notes'];

    protected $casts = ['total' => 'decimal:2', 'sold_at' => 'datetime'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paidAmount(): float
    {
        return (float) $this->payments()
            ->where('status', \App\Enums\PaymentStatus::Paid->value)
            ->sum('amount');
    }

    public function balance(): float
    {
        return (float) $this->total - $this->paidAmount();
    }
}
```

`app/Models/SaleItem.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = ['sale_id', 'platform_id', 'profile_id', 'description', 'price', 'months'];

    protected $casts = ['price' => 'decimal:2'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }
}
```

`app/Models/Payment.php`:
```php
<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['sale_id', 'amount', 'method', 'status', 'paid_at'];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => PaymentStatus::class,
        'paid_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
```

- [ ] **Step 4: Factories**

`SaleFactory` `definition()`:
```php
return [
    'customer_id' => \App\Models\Customer::factory(),
    'seller_id' => null,
    'total' => fake()->randomFloat(2, 5, 50),
    'sold_at' => now(),
];
```
`SaleItemFactory` `definition()`:
```php
return [
    'sale_id' => \App\Models\Sale::factory(),
    'platform_id' => \App\Models\Platform::factory(),
    'profile_id' => null,
    'description' => 'Perfil Netflix 1 mes',
    'price' => fake()->randomFloat(2, 2, 15),
    'months' => 1,
];
```
`PaymentFactory` `definition()`:
```php
return [
    'sale_id' => \App\Models\Sale::factory(),
    'amount' => fake()->randomFloat(2, 5, 50),
    'method' => 'efectivo',
    'status' => \App\Enums\PaymentStatus::Paid->value,
    'paid_at' => now(),
];
```

- [ ] **Step 5: Test de balance**

`tests/Feature/SaleModelTest.php`:
```php
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
```

- [ ] **Step 6: Ejecutar el test**

Run: `& "C:\xampp\php\php.exe" artisan test --filter=SaleModelTest`
Expected: 1 passed.

- [ ] **Step 7: Commit**

```powershell
git add -A
git commit -m "feat: modelos Sale, SaleItem y Payment con calculo de saldo"
git push
```

---

## Task 9: Modelo Subscription + lógica de vencimiento

**Files:**
- Create: `app/Models/Subscription.php`, migración, factory, `app/Services/SubscriptionService.php`
- Test: `tests/Unit/SubscriptionServiceTest.php`

- [ ] **Step 1: Generar**

```powershell
& "C:\xampp\php\php.exe" artisan make:model Subscription -mf
```

- [ ] **Step 2: Migración**

```php
Schema::create('subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
    $table->foreignId('profile_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
    $table->date('starts_at');
    $table->date('expires_at');
    $table->string('status')->default('active'); // SubscriptionStatus
    $table->timestamp('cancelled_at')->nullable();
    $table->boolean('reminder_sent')->default(false);
    $table->timestamps();
});
```

- [ ] **Step 3: Modelo**

`app/Models/Subscription.php`:
```php
<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'profile_id', 'sale_id', 'starts_at',
        'expires_at', 'status', 'cancelled_at', 'reminder_sent',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'expires_at' => 'date',
        'cancelled_at' => 'datetime',
        'reminder_sent' => 'boolean',
        'status' => SubscriptionStatus::class,
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function daysUntilExpiry(): int
    {
        return now()->startOfDay()->diffInDays($this->expires_at, false);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::Active->value);
    }

    public function scopeExpiringWithin($query, int $days)
    {
        return $query->active()
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->whereDate('expires_at', '<=', now()->addDays($days)->toDateString());
    }
}
```

- [ ] **Step 4: Factory**

`SubscriptionFactory` `definition()`:
```php
return [
    'customer_id' => \App\Models\Customer::factory(),
    'profile_id' => null,
    'sale_id' => null,
    'starts_at' => now()->subDays(10),
    'expires_at' => now()->addDays(20),
    'status' => \App\Enums\SubscriptionStatus::Active->value,
    'reminder_sent' => false,
];
```

- [ ] **Step 5: Crear el servicio**

`app/Services/SubscriptionService.php`:
```php
<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Support\Collection;

class SubscriptionService
{
    /** Suscripciones activas que vencen dentro de N días (incluye hoy). */
    public function expiringWithin(int $days): Collection
    {
        return Subscription::expiringWithin($days)->get();
    }

    /** Marca como vencidas las suscripciones activas cuya fecha ya pasó. */
    public function markExpired(): int
    {
        return Subscription::active()
            ->whereDate('expires_at', '<', now()->toDateString())
            ->update(['status' => \App\Enums\SubscriptionStatus::Expired->value]);
    }
}
```

- [ ] **Step 6: Escribir el test**

`tests/Unit/SubscriptionServiceTest.php`:
```php
<?php

use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Enums\SubscriptionStatus;

it('lista las suscripciones que vencen dentro de N dias', function () {
    Subscription::factory()->create(['expires_at' => now()->addDays(2)]);  // dentro
    Subscription::factory()->create(['expires_at' => now()->addDays(10)]); // fuera
    Subscription::factory()->create(['expires_at' => now()->subDay()]);    // ya vencida

    $service = new SubscriptionService();
    expect($service->expiringWithin(3))->toHaveCount(1);
});

it('marca como vencidas las suscripciones cuya fecha paso', function () {
    Subscription::factory()->create(['expires_at' => now()->subDay()]);
    Subscription::factory()->create(['expires_at' => now()->addDay()]);

    $service = new SubscriptionService();
    $count = $service->markExpired();

    expect($count)->toBe(1)
        ->and(Subscription::where('status', SubscriptionStatus::Expired->value)->count())->toBe(1);
});
```

- [ ] **Step 7: Ejecutar el test**

Run: `& "C:\xampp\php\php.exe" artisan test --filter=SubscriptionServiceTest`
Expected: 2 passed.

- [ ] **Step 8: Recurso Filament**

```powershell
& "C:\xampp\php\php.exe" artisan make:filament-resource Subscription --generate
```
Form mínimo:
```php
Forms\Components\Select::make('customer_id')->relationship('customer', 'name')->required(),
Forms\Components\Select::make('profile_id')->relationship('profile', 'name'),
Forms\Components\DatePicker::make('starts_at')->required()->default(now()),
Forms\Components\DatePicker::make('expires_at')->required(),
Forms\Components\Select::make('status')
    ->options(collect(\App\Enums\SubscriptionStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
    ->default('active'),
```

- [ ] **Step 9: Commit**

```powershell
git add -A
git commit -m "feat: Subscription + SubscriptionService (vencimientos)"
git push
```

---

## Task 10: SaleService — crear venta completa (transacción)

**Files:**
- Create: `app/Services/SaleService.php`
- Test: `tests/Unit/SaleServiceTest.php`

- [ ] **Step 1: Escribir el test primero**

`tests/Unit/SaleServiceTest.php`:
```php
<?php

use App\Models\Customer;
use App\Models\Platform;
use App\Models\Account;
use App\Models\Profile;
use App\Services\SaleService;
use App\Enums\ProfileStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;

it('crea venta, pago, suscripcion y asigna el perfil', function () {
    $customer = Customer::factory()->create();
    $platform = Platform::factory()->create(['base_price' => 10]);
    $account = Account::factory()->create(['platform_id' => $platform->id]);
    $profile = Profile::factory()->create([
        'account_id' => $account->id,
        'status' => ProfileStatus::Free->value,
    ]);

    $service = new SaleService();
    $sale = $service->create(
        customer: $customer,
        profile: $profile,
        price: 10.0,
        months: 1,
        paymentMethod: 'efectivo',
    );

    // Venta y total
    expect((float) $sale->total)->toBe(10.0)
        ->and($sale->items)->toHaveCount(1);

    // Pago registrado y saldo cero
    expect($sale->paidAmount())->toBe(10.0)
        ->and($sale->balance())->toBe(0.0);

    // Perfil quedó asignado
    expect($profile->fresh()->status)->toBe(ProfileStatus::Assigned);

    // Suscripción creada, activa, vence en ~1 mes
    $sub = $customer->subscriptions()->first();
    expect($sub)->not->toBeNull()
        ->and($sub->status)->toBe(SubscriptionStatus::Active)
        ->and($sub->profile_id)->toBe($profile->id)
        ->and($sub->expires_at->toDateString())->toBe(now()->addMonth()->toDateString());
});

it('no permite vender un perfil ya asignado', function () {
    $profile = Profile::factory()->create(['status' => ProfileStatus::Assigned->value]);
    $customer = Customer::factory()->create();

    $service = new SaleService();
    $service->create($customer, $profile, 10.0, 1, 'efectivo');
})->throws(\RuntimeException::class);
```

- [ ] **Step 2: Ejecutar el test (debe fallar)**

Run: `& "C:\xampp\php\php.exe" artisan test --filter=SaleServiceTest`
Expected: FAIL ("Class App\Services\SaleService not found").

- [ ] **Step 3: Implementar el servicio**

`app/Services/SaleService.php`:
```php
<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ProfileStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Profile;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function create(
        Customer $customer,
        Profile $profile,
        float $price,
        int $months,
        string $paymentMethod,
    ): Sale {
        if ($profile->status !== ProfileStatus::Free) {
            throw new \RuntimeException('El perfil no está disponible.');
        }

        return DB::transaction(function () use ($customer, $profile, $price, $months, $paymentMethod) {
            $sale = Sale::create([
                'customer_id' => $customer->id,
                'seller_id' => $customer->seller_id,
                'total' => $price,
                'sold_at' => now(),
            ]);

            $platformId = $profile->account->platform_id;

            $sale->items()->create([
                'platform_id' => $platformId,
                'profile_id' => $profile->id,
                'description' => "Perfil {$profile->name} x{$months} mes(es)",
                'price' => $price,
                'months' => $months,
            ]);

            $sale->payments()->create([
                'amount' => $price,
                'method' => $paymentMethod,
                'status' => PaymentStatus::Paid->value,
                'paid_at' => now(),
            ]);

            $profile->update(['status' => ProfileStatus::Assigned->value]);

            $customer->subscriptions()->create([
                'profile_id' => $profile->id,
                'sale_id' => $sale->id,
                'starts_at' => now()->toDateString(),
                'expires_at' => now()->addMonths($months)->toDateString(),
                'status' => SubscriptionStatus::Active->value,
            ]);

            return $sale->load('items', 'payments');
        });
    }
}
```

- [ ] **Step 4: Ejecutar el test (debe pasar)**

Run: `& "C:\xampp\php\php.exe" artisan test --filter=SaleServiceTest`
Expected: 2 passed.

- [ ] **Step 5: Commit**

```powershell
git add -A
git commit -m "feat: SaleService crea venta+pago+suscripcion y asigna perfil"
git push
```

---

## Task 11: Permisos por rol en los recursos Filament

**Files:**
- Modify: cada `*Resource.php` (Account, Platform, Customer, Sale, Subscription, User)
- Create: `app/Policies/` según haga falta, `tests/Feature/ResourceVisibilityTest.php`

- [ ] **Step 1: Restringir visibilidad de recursos por rol**

En recursos solo para Admin (ej. `PlatformResource`, `AccountResource`, gestión de usuarios), añadir:
```php
public static function canAccess(): bool
{
    return auth()->user()?->hasRole('admin') ?? false;
}
```
En recursos compartidos (Customer, Sale, Subscription): permitir `admin`, `vendedor`; Soporte solo lectura (sobrescribir `canCreate`, `canEdit`, `canDelete` para devolver `false` si es `soporte`).

- [ ] **Step 2: Recurso de gestión de Usuarios (solo admin)**

```powershell
& "C:\xampp\php\php.exe" artisan make:filament-resource User --generate
```
Añadir `canAccess()` restringido a `admin` y un campo de selección de rol:
```php
Forms\Components\Select::make('roles')
    ->relationship('roles', 'name')
    ->multiple()
    ->preload(),
Forms\Components\TextInput::make('name')->required(),
Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
Forms\Components\TextInput::make('password')
    ->password()
    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
    ->dehydrated(fn ($state) => filled($state))
    ->required(fn (string $context) => $context === 'create'),
```

- [ ] **Step 3: Test de visibilidad por rol**

`tests/Feature/ResourceVisibilityTest.php`:
```php
<?php

use App\Models\User;
use App\Filament\Resources\PlatformResource;
use App\Filament\Resources\CustomerResource;
use Spatie\Permission\Models\Role;

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
```

- [ ] **Step 4: Ejecutar el test**

Run: `& "C:\xampp\php\php.exe" artisan test --filter=ResourceVisibilityTest`
Expected: 2 passed.

- [ ] **Step 5: Commit**

```powershell
git add -A
git commit -m "feat: visibilidad de recursos por rol (admin/vendedor/soporte)"
git push
```

---

## Task 12: Dashboard — widgets de vencimientos y stock

**Files:**
- Create: `app/Filament/Widgets/ExpiringSubscriptionsWidget.php`, `app/Filament/Widgets/StatsOverviewWidget.php`
- Test: `tests/Feature/DashboardWidgetTest.php`

- [ ] **Step 1: Widget de stats**

```powershell
& "C:\xampp\php\php.exe" artisan make:filament-widget StatsOverviewWidget --stats-overview
```
`app/Filament/Widgets/StatsOverviewWidget.php`:
```php
<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Profile;
use App\Services\SubscriptionService;
use App\Enums\ProfileStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $expiringSoon = (new SubscriptionService())->expiringWithin(3)->count();
        $freeProfiles = Profile::where('status', ProfileStatus::Free->value)->count();

        return [
            Stat::make('Clientes', Customer::count()),
            Stat::make('Perfiles libres (stock)', $freeProfiles),
            Stat::make('Vencen en 3 días', $expiringSoon)
                ->color($expiringSoon > 0 ? 'warning' : 'success'),
        ];
    }
}
```

- [ ] **Step 2: Widget de tabla de próximos vencimientos**

```powershell
& "C:\xampp\php\php.exe" artisan make:filament-widget ExpiringSubscriptionsWidget --table
```
En `getTableQuery()` usar:
```php
return \App\Models\Subscription::query()
    ->expiringWithin(7)
    ->with(['customer', 'profile.account.platform']);
```
Columnas: `customer.name`, `profile.account.platform.name`, `expires_at` (date), días restantes.

- [ ] **Step 3: Test del widget de stats**

`tests/Feature/DashboardWidgetTest.php`:
```php
<?php

use App\Models\Subscription;
use App\Services\SubscriptionService;

it('cuenta las suscripciones próximas a vencer para el dashboard', function () {
    Subscription::factory()->create(['expires_at' => now()->addDays(2)]);
    Subscription::factory()->create(['expires_at' => now()->addDays(30)]);

    expect((new SubscriptionService())->expiringWithin(3)->count())->toBe(1);
});
```

- [ ] **Step 4: Ejecutar el test**

Run: `& "C:\xampp\php\php.exe" artisan test --filter=DashboardWidgetTest`
Expected: 1 passed.

- [ ] **Step 5: Verificar en el panel**

`artisan serve` → `/admin` → el dashboard muestra las stats y la tabla de vencimientos. Detener.

- [ ] **Step 6: Commit**

```powershell
git add -A
git commit -m "feat: dashboard con stats y proximos vencimientos"
git push
```

---

## Task 13: PWA — instalable como app de escritorio

**Files:**
- Create: `public/manifest.json`, `public/sw.js`, `public/icons/icon-192.png`, `public/icons/icon-512.png`
- Modify: layout/head para registrar el manifest y el service worker

- [ ] **Step 1: Crear el manifest**

`public/manifest.json`:
```json
{
  "name": "RebootCRM",
  "short_name": "RebootCRM",
  "start_url": "/admin",
  "display": "standalone",
  "background_color": "#0f172a",
  "theme_color": "#0f172a",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```

- [ ] **Step 2: Crear el service worker**

`public/sw.js`:
```javascript
const CACHE = 'rebootcrm-v1';
self.addEventListener('install', (e) => self.skipWaiting());
self.addEventListener('activate', (e) => self.clients.claim());
self.addEventListener('fetch', (e) => {
  // Network-first: la app es dinámica; el SW solo habilita la instalación.
  e.respondWith(fetch(e.request).catch(() => caches.match(e.request)));
});
```

- [ ] **Step 3: Generar íconos**

Colocar dos PNG (192x192 y 512x512) con el logo en `public/icons/`. Provisionalmente, exportar cualquier logo cuadrado a esos tamaños.

- [ ] **Step 4: Inyectar manifest y registrar SW en el head de Filament**

En `AdminPanelProvider.php`, dentro de `panel(...)`, usar render hooks:
```php
use Filament\View\PanelsRenderHook;
// ...
->renderHook(
    PanelsRenderHook::HEAD_END,
    fn (): string => '<link rel="manifest" href="/manifest.json">'
        . '<meta name="theme-color" content="#0f172a">'
)
->renderHook(
    PanelsRenderHook::BODY_END,
    fn (): string => '<script>if("serviceWorker" in navigator){navigator.serviceWorker.register("/sw.js");}</script>'
)
```

- [ ] **Step 5: Verificar instalación**

`artisan serve` → abrir `http://localhost:8000/admin` en Chrome/Edge → debe aparecer el ícono de "Instalar app" en la barra de direcciones. Instalar y comprobar que abre en ventana propia.

> Nota: PWA requiere HTTPS en producción; `localhost` está exento, por eso funciona en dev.

- [ ] **Step 6: Commit**

```powershell
git add -A
git commit -m "feat: PWA instalable (manifest + service worker)"
git push
```

---

## Task 14: Seeder de demo + verificación final

**Files:**
- Create: `database/seeders/DemoSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Crear el DemoSeeder**

`database/seeders/DemoSeeder.php`: crea 2 plataformas (Netflix, Disney+), 2 cuentas con 5 perfiles cada una, 3 clientes y usa `SaleService` para registrar 2 ventas con sus suscripciones (una venciendo en 2 días para ver el aviso).
```php
<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Platform;
use App\Models\Profile;
use App\Services\SaleService;
use App\Enums\ProfileStatus;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $service = new SaleService();

        foreach (['Netflix' => 9.99, 'Disney+' => 7.99] as $name => $price) {
            $platform = Platform::firstOrCreate(['name' => $name], ['base_price' => $price, 'profiles_per_account' => 5]);
            $account = Account::factory()->create(['platform_id' => $platform->id, 'profiles_total' => 5]);
            Profile::factory()->count(5)->create([
                'account_id' => $account->id,
                'status' => ProfileStatus::Free->value,
            ]);
        }

        $customers = Customer::factory()->count(3)->create();

        $freeProfiles = Profile::where('status', ProfileStatus::Free->value)->take(2)->get();
        $service->create($customers[0], $freeProfiles[0], 5.0, 1, 'efectivo');
        $service->create($customers[1], $freeProfiles[1], 5.0, 1, 'transferencia');
    }
}
```

- [ ] **Step 2: Registrar en DatabaseSeeder (solo en dev)**

Añadir al final del `run()` de `DatabaseSeeder`:
```php
if (app()->environment('local')) {
    $this->call(DemoSeeder::class);
}
```

- [ ] **Step 3: Ejecutar migración limpia + seed**

```powershell
& "C:\xampp\php\php.exe" artisan migrate:fresh --seed
```
Expected: sin errores; datos de demo cargados.

- [ ] **Step 4: Suite completa de tests**

Run: `& "C:\xampp\php\php.exe" artisan test`
Expected: todos los tests en verde.

- [ ] **Step 5: Verificación manual end-to-end**

`artisan serve` → `/admin`:
- Login con `admin@rebootcrm.test`.
- Ver dashboard con stats y vencimientos.
- Crear plataforma, cuenta con perfiles, cliente.
- Ver clientes y suscripciones.

- [ ] **Step 6: Commit final de Fase 1**

```powershell
git add -A
git commit -m "feat: seeder de demo y cierre de Fase 1 (MVP CRM)"
git push
```

---

## Criterios de aceptación (Fase 1)

- [ ] Composer instalado y Laravel 11 corriendo sobre XAMPP.
- [ ] Panel Filament en `/admin` con login.
- [ ] Roles Admin/Vendedor/Soporte; cada rol ve solo lo permitido.
- [ ] CRUD de Plataformas, Cuentas (password cifrada) y Perfiles.
- [ ] CRUD de Clientes.
- [ ] Registro de Ventas con pago y generación de Suscripción + asignación de perfil (vía `SaleService`).
- [ ] Dashboard muestra stock de perfiles libres y suscripciones próximas a vencer.
- [ ] App instalable como PWA en el escritorio.
- [ ] Suite de tests en verde.

---

## Notas para Fase 2 (fuera de alcance aquí)

- Conexión WhatsApp Cloud API multi-número, plantillas, cola de envío.
- Comando programado (`php artisan schedule`) que use `SubscriptionService::expiringWithin()` para enviar recordatorios y marque `reminder_sent`.
- El campo `subscriptions.reminder_sent` ya está creado para esto.
```
