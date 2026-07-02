# RebootCRM — Fase 2 (WhatsApp saliente) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development.
> Steps use checkbox (`- [ ]`). Tests con Pest + `Http::fake()` (sin llamadas reales a Meta).

**Goal:** Que el CRM envíe mensajes por WhatsApp vía la Cloud API oficial: (a) los datos de
la cuenta al concretarse una venta, y (b) recordatorios de vencimiento automáticos. Multi-número.

**Architecture:** Un servicio `WhatsAppService` encapsula la Cloud API (HTTP). Los números se
guardan en `whatsapp_numbers`. Cada envío se registra en `whatsapp_messages`. El envío al vender
se dispara tras `SaleService::create`. Un comando Artisan programado envía los recordatorios.

**Tech Stack:** Laravel 12, Filament 3.3, Http client de Laravel, Pest.

---

## Notas de entorno (iguales a Fase 1)
- Dir: `c:\xampp\htdocs\CRM`. Rama: `feature/fase2-whatsapp`.
- PHP: `$php = "C:\xampp\php\php.exe"`; `& $php artisan ...`. PowerShell, nada interactivo, no `artisan serve`.
- MariaDB corriendo; `.env` → `rebootcrm`. `& $php artisan migrate --force` tras migraciones.
- Pest instalado; `tests/Pest.php` aplica RefreshDatabase a Feature/Unit. Tests usan `Http::fake()`.
- Commits firmados con `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`, sin push (lo hace el controlador).

---

## Task 1: Config + modelo WhatsappNumber (multi-número)

**Files:** `config/whatsapp.php`, `.env`/`.env.example`, `app/Models/WhatsappNumber.php`, migración, factory, `app/Filament/Resources/WhatsappNumberResource.php`, `tests/Feature/WhatsappNumberTest.php`

- [ ] **Step 1: Config `config/whatsapp.php`**
```php
<?php

return [
    // Versión de la Graph API de Meta
    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
    // Token de verificación del webhook (Fase 3, pero se deja definido)
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'rebootcrm-verify'),
    // Nombres de las plantillas aprobadas en Meta
    'templates' => [
        'delivery' => env('WHATSAPP_TPL_DELIVERY', 'entrega_cuenta'),
        'reminder' => env('WHATSAPP_TPL_REMINDER', 'recordatorio_vencimiento'),
    ],
    // Idioma de las plantillas
    'template_lang' => env('WHATSAPP_TPL_LANG', 'es'),
    // Días antes del vencimiento para el recordatorio
    'reminder_days' => (int) env('WHATSAPP_REMINDER_DAYS', 3),
];
```

- [ ] **Step 2: Añadir claves a `.env` y `.env.example`**
```env
WHATSAPP_API_VERSION=v21.0
WHATSAPP_VERIFY_TOKEN=rebootcrm-verify
WHATSAPP_TPL_DELIVERY=entrega_cuenta
WHATSAPP_TPL_REMINDER=recordatorio_vencimiento
WHATSAPP_TPL_LANG=es
WHATSAPP_REMINDER_DAYS=3
```

- [ ] **Step 3: Generar modelo + migración + factory**
`& $php artisan make:model WhatsappNumber -mf`

- [ ] **Step 4: Migración**
```php
Schema::create('whatsapp_numbers', function (Blueprint $table) {
    $table->id();
    $table->string('label');                 // etiqueta interna (ej. "Ventas MX")
    $table->string('phone_number_id');       // ID del número en Meta
    $table->string('display_number')->nullable(); // el número visible, ej. 521...
    $table->text('access_token');            // token de Meta (cifrado)
    $table->boolean('is_default')->default(false);
    $table->boolean('active')->default(true);
    $table->timestamps();
});
```

- [ ] **Step 5: Modelo `app/Models/WhatsappNumber.php`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'label', 'phone_number_id', 'display_number',
        'access_token', 'is_default', 'active',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'is_default' => 'boolean',
        'active' => 'boolean',
    ];

    /** Número por defecto para enviar (o el primero activo). */
    public static function default(): ?self
    {
        return static::where('active', true)
            ->orderByDesc('is_default')
            ->first();
    }
}
```

- [ ] **Step 6: Factory `WhatsappNumberFactory` definition()**
```php
return [
    'label' => 'Ventas',
    'phone_number_id' => (string) fake()->numerify('##############'),
    'display_number' => '521' . fake()->numerify('##########'),
    'access_token' => 'test-token',
    'is_default' => true,
    'active' => true,
];
```

- [ ] **Step 7: Test `tests/Feature/WhatsappNumberTest.php` (Pest)**
```php
<?php

use App\Models\WhatsappNumber;
use Illuminate\Support\Facades\DB;

it('cifra el token y devuelve el numero por defecto', function () {
    WhatsappNumber::factory()->create(['is_default' => false, 'label' => 'A']);
    $def = WhatsappNumber::factory()->create(['is_default' => true, 'label' => 'B', 'access_token' => 'secreto']);

    // token cifrado en BD
    $raw = DB::table('whatsapp_numbers')->where('id', $def->id)->value('access_token');
    expect($raw)->not->toBe('secreto');
    expect($def->access_token)->toBe('secreto');

    // default() devuelve el marcado por defecto
    expect(WhatsappNumber::default()->id)->toBe($def->id);
});
```

- [ ] **Step 8: Ejecutar test → 1 passed.** `& $php artisan test --filter=WhatsappNumberTest`

- [ ] **Step 9: Recurso Filament (solo admin)**
`& $php artisan make:filament-resource WhatsappNumber --generate`
Form: `label` required; `phone_number_id` required; `display_number`; `access_token` como `TextInput->password()->revealable()`; `is_default` Toggle; `active` Toggle. En la tabla NO mostrar `access_token`. Añadir `public static function canAccess(): bool { return auth()->user()?->hasRole('admin') ?? false; }`.

- [ ] **Step 10: Migrar + commit.**
`& $php artisan migrate --force`; commit `feat(f2): config whatsapp + modelo WhatsappNumber (multi-numero)`.

---

## Task 2: WhatsAppService + log de mensajes

**Files:** `app/Models/WhatsappMessage.php` + migración + factory, `app/Services/WhatsAppService.php`, `tests/Feature/WhatsAppServiceTest.php`

- [ ] **Step 1: Modelo de log** `& $php artisan make:model WhatsappMessage -mf`

- [ ] **Step 2: Migración `whatsapp_messages`**
```php
Schema::create('whatsapp_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('whatsapp_number_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
    $table->string('to');                 // número destino
    $table->string('direction')->default('outbound'); // outbound|inbound
    $table->string('type')->default('template');       // template|text
    $table->text('body')->nullable();     // resumen/legible del mensaje
    $table->string('wa_message_id')->nullable(); // id que devuelve Meta
    $table->string('status')->default('sent');   // sent|failed
    $table->text('error')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 3: Modelo `WhatsappMessage`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_number_id', 'customer_id', 'to', 'direction',
        'type', 'body', 'wa_message_id', 'status', 'error',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
```

- [ ] **Step 4: Factory `WhatsappMessageFactory` definition()**
```php
return [
    'to' => '521' . fake()->numerify('##########'),
    'direction' => 'outbound',
    'type' => 'template',
    'body' => 'mensaje de prueba',
    'status' => 'sent',
];
```

- [ ] **Step 5: Servicio `app/Services/WhatsAppService.php`**
```php
<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\WhatsappMessage;
use App\Models\WhatsappNumber;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    public function __construct(private ?WhatsappNumber $number = null)
    {
        $this->number = $number ?? WhatsappNumber::default();
    }

    private function endpoint(): string
    {
        $version = config('whatsapp.api_version');
        return "https://graph.facebook.com/{$version}/{$this->number->phone_number_id}/messages";
    }

    /** Envía un mensaje de texto libre (solo válido dentro de la ventana de 24h). */
    public function sendText(string $to, string $text, ?Customer $customer = null): WhatsappMessage
    {
        return $this->send($to, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $text],
        ], 'text', $text, $customer);
    }

    /**
     * Envía una plantilla aprobada.
     * @param array<int,string> $params Parámetros posicionales ({{1}}, {{2}}, ...).
     */
    public function sendTemplate(string $to, string $template, array $params, ?Customer $customer = null): WhatsappMessage
    {
        $components = [[
            'type' => 'body',
            'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => (string) $p], $params),
        ]];

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => config('whatsapp.template_lang')],
                'components' => $components,
            ],
        ];

        $body = $template . ': ' . implode(' | ', $params);
        return $this->send($to, $payload, 'template', $body, $customer);
    }

    private function send(string $to, array $payload, string $type, string $body, ?Customer $customer): WhatsappMessage
    {
        if (! $this->number) {
            throw new \RuntimeException('No hay número de WhatsApp configurado.');
        }

        $log = [
            'whatsapp_number_id' => $this->number->id,
            'customer_id' => $customer?->id,
            'to' => $to,
            'direction' => 'outbound',
            'type' => $type,
            'body' => $body,
        ];

        try {
            $response = Http::withToken($this->number->access_token)
                ->post($this->endpoint(), $payload);

            if ($response->failed()) {
                return WhatsappMessage::create($log + [
                    'status' => 'failed',
                    'error' => $response->body(),
                ]);
            }

            return WhatsappMessage::create($log + [
                'status' => 'sent',
                'wa_message_id' => data_get($response->json(), 'messages.0.id'),
            ]);
        } catch (\Throwable $e) {
            return WhatsappMessage::create($log + [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

- [ ] **Step 6: Test `tests/Feature/WhatsAppServiceTest.php` (Pest con Http::fake)**
```php
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
```

- [ ] **Step 7: Ejecutar test → 2 passed.** `& $php artisan test --filter=WhatsAppServiceTest`

- [ ] **Step 8: Migrar + commit** `feat(f2): WhatsAppService (texto/plantilla) + log whatsapp_messages`.

---

## Task 3: Enviar datos de la cuenta al concretar la venta

**Files:** `app/Services/SaleService.php` (modificar), `tests/Unit/SaleDeliveryTest.php`

- [ ] **Step 1: Añadir envío tras crear la venta en `SaleService`**
Modifica `SaleService::create(...)`: acepta un flag opcional `bool $notify = true` y, tras
crear la suscripción DENTRO de la transacción pero **el envío va DESPUÉS del commit**, llama a
un método privado que arme y mande la plantilla de entrega. Estructura:
```php
public function create(
    Customer $customer,
    Profile $profile,
    float $price,
    int $months,
    string $paymentMethod,
    bool $notify = true,
): Sale {
    // ... (código existente de validación + DB::transaction que devuelve $sale) ...

    $sale = DB::transaction(function () use (...) { /* existente */ });

    if ($notify && $customer->phone) {
        $this->notifyDelivery($customer, $profile, $sale);
    }

    return $sale;
}

private function notifyDelivery(Customer $customer, Profile $profile, Sale $sale): void
{
    $account = $profile->account()->with('platform')->first();
    $subscription = $sale->customer->subscriptions()->latest('id')->first();

    app(\App\Services\WhatsAppService::class)->sendTemplate(
        to: $customer->phone,
        template: config('whatsapp.templates.delivery'),
        params: [
            $customer->name,
            $account->platform->name ?? 'streaming',
            $account->email,
            $account->password,
            $profile->name,
            optional($subscription)->expires_at?->format('d/m/Y') ?? '',
        ],
        customer: $customer,
    );
}
```
IMPORTANTE: el envío va **fuera** de la transacción (tras el commit) para no revertir la venta
si WhatsApp falla. `WhatsAppService` ya captura errores y los registra sin lanzar excepción
(salvo "no hay número configurado", que sí lanza; por eso el envío es best-effort — envuelve
la llamada en try/catch en `notifyDelivery` para que un fallo de WhatsApp NUNCA rompa la venta).
Ajuste final de `notifyDelivery`: envuelve el `app(...)->sendTemplate(...)` en try/catch que
registre el error en el log de Laravel (`report($e)`) y continúe.

- [ ] **Step 2: Test `tests/Unit/SaleDeliveryTest.php` (Pest con Http::fake)**
```php
<?php

use App\Models\Customer;
use App\Models\Platform;
use App\Models\Account;
use App\Models\Profile;
use App\Models\WhatsappNumber;
use App\Models\WhatsappMessage;
use App\Services\SaleService;
use App\Enums\ProfileStatus;
use Illuminate\Support\Facades\Http;

it('envia los datos de la cuenta por WhatsApp al vender', function () {
    WhatsappNumber::factory()->create(['is_default' => true, 'phone_number_id' => '123', 'access_token' => 'tok']);
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);

    $customer = Customer::factory()->create(['phone' => '5215500000000']);
    $platform = Platform::factory()->create();
    $account = Account::factory()->create(['platform_id' => $platform->id]);
    $profile = Profile::factory()->create(['account_id' => $account->id, 'status' => ProfileStatus::Free->value]);

    (new SaleService())->create($customer, $profile, 10.0, 1, 'efectivo');

    expect(WhatsappMessage::where('customer_id', $customer->id)->where('status', 'sent')->count())->toBe(1);
    Http::assertSent(fn ($r) => $r['type'] === 'template');
});

it('no rompe la venta si no hay whatsapp configurado', function () {
    // Sin WhatsappNumber → el envío falla suave, la venta se completa igual
    $customer = Customer::factory()->create(['phone' => '5215500000000']);
    $platform = Platform::factory()->create();
    $account = Account::factory()->create(['platform_id' => $platform->id]);
    $profile = Profile::factory()->create(['account_id' => $account->id, 'status' => ProfileStatus::Free->value]);

    $sale = (new SaleService())->create($customer, $profile, 10.0, 1, 'efectivo');

    expect($sale->exists)->toBeTrue()
        ->and($profile->fresh()->status)->toBe(ProfileStatus::Assigned);
});
```

- [ ] **Step 3: Ejecutar test → 2 passed.** Asegúrate de que el 2º test pase (envío best-effort, no rompe la venta).

- [ ] **Step 4: Suite completa + commit** `feat(f2): enviar datos de cuenta por WhatsApp al vender`.

---

## Task 4: Recordatorios de vencimiento (comando programado)

**Files:** `app/Console/Commands/SendExpiryReminders.php`, registro en scheduler (`routes/console.php` o `bootstrap/app.php`), `tests/Feature/ExpiryRemindersTest.php`

- [ ] **Step 1: Crear el comando** `& $php artisan make:command SendExpiryReminders`
```php
<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendExpiryReminders extends Command
{
    protected $signature = 'whatsapp:send-reminders';
    protected $description = 'Envía recordatorios de vencimiento por WhatsApp a suscripciones próximas a vencer';

    public function handle(SubscriptionService $subscriptions, WhatsAppService $whatsapp): int
    {
        $days = config('whatsapp.reminder_days');
        $subs = $subscriptions->expiringWithin($days)
            ->load('customer', 'profile.account.platform')
            ->filter(fn (Subscription $s) => ! $s->reminder_sent && $s->customer && $s->customer->phone);

        $sent = 0;
        foreach ($subs as $sub) {
            $whatsapp->sendTemplate(
                to: $sub->customer->phone,
                template: config('whatsapp.templates.reminder'),
                params: [
                    $sub->customer->name,
                    $sub->profile->account->platform->name ?? 'streaming',
                    $sub->expires_at->format('d/m/Y'),
                ],
                customer: $sub->customer,
            );
            $sub->update(['reminder_sent' => true]);
            $sent++;
        }

        $this->info("Recordatorios enviados: {$sent}");
        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Programar el comando (scheduler)**
En `routes/console.php` añade:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('whatsapp:send-reminders')->dailyAt('09:00');
```

- [ ] **Step 3: Test `tests/Feature/ExpiryRemindersTest.php` (Pest con Http::fake)**
```php
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
    // vence en 2 días → dentro del rango
    Subscription::factory()->create(['customer_id' => $customer->id, 'expires_at' => now()->addDays(2), 'reminder_sent' => false]);
    // vence en 30 días → fuera
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
```

- [ ] **Step 4: Ejecutar test → 2 passed.**

- [ ] **Step 5: Suite completa + commit** `feat(f2): comando de recordatorios de vencimiento + scheduler`.

---

## Criterios de aceptación (Fase 2)
- [ ] `WhatsappNumber` gestionable en el panel (solo admin), token cifrado.
- [ ] `WhatsAppService` envía texto y plantillas vía Cloud API, registrando cada envío (sent/failed) sin romper el flujo.
- [ ] Al concretar una venta con `SaleService`, se envían los datos de la cuenta por WhatsApp (si hay número y teléfono).
- [ ] Comando `whatsapp:send-reminders` envía recordatorios a los que vencen dentro de N días y marca `reminder_sent`; programado a diario.
- [ ] Toda la suite de tests en verde (Fase 1 + Fase 2), con `Http::fake()` (sin llamadas reales a Meta).

## Notas para producción / despliegue (fuera de esta fase)
- Configurar el cron de cPanel: `* * * * * php /ruta/artisan schedule:run` (una línea; el scheduler decide qué correr).
- Pegar credenciales reales de Meta en el panel (WhatsappNumber) y crear las plantillas aprobadas.
- Webhook entrante (recibir mensajes / estados) es Fase 3.
