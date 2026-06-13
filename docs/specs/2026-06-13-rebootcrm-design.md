# RebootCRM — Especificación de Diseño

- **Fecha:** 2026-06-13
- **Estado:** Aprobado (diseño)
- **Repositorio:** https://github.com/ByZoro99/RebootCRM
- **Autor:** ByZoro99 (con asistencia de Claude)

---

## 1. Resumen ejecutivo

RebootCRM es un sistema de gestión (CRM) para el negocio de **venta de cuentas de
streaming** (Netflix, Disney+, Spotify, etc.). Centraliza clientes, inventario de
cuentas, ventas, pagos y suscripciones con vencimiento, e integra **WhatsApp mediante
la API oficial (Cloud API de Meta)** para automatizar comunicaciones y atención sin
riesgo de baneo, con soporte **multi-número**.

Es una **aplicación web en Laravel**, instalable como **app de escritorio (PWA)**, con
**roles** (admin, vendedor, soporte), un **bot de atención** con opción de "hablar con
un humano", y diseñada desde el inicio para **conectarse a una web de ventas externa**.

---

## 2. Objetivos

- Llevar el control completo de ventas de cuentas de streaming.
- Conectar **varios números de WhatsApp sin riesgo de ban** (vía API oficial).
- Automatizar: confirmación de venta, entrega de datos de cuenta, recordatorios de
  vencimiento/renovación.
- Atender clientes con un **bot** para dudas/soporte y derivación a un humano.
- Permitir trabajo **multi-usuario con roles** diferenciados.
- Quedar **preparado para integrarse con una web de ventas** en el futuro.

### No-objetivos (por ahora)
- Pasarela de pagos en línea automática (los pagos se registran manualmente al inicio).
- App móvil nativa (la PWA cubre el uso en celular).
- IA conversacional avanzada (queda como fase opcional posterior).

---

## 3. Decisiones tomadas

| Tema | Decisión | Motivo |
|------|----------|--------|
| Conexión WhatsApp | **Cloud API oficial de Meta** | Único camino con **cero riesgo de ban** y multi-número nativo |
| Stack | **Laravel 11 (PHP 8.2+)** | Aprovecha experiencia previa en PHP/XAMPP; robusto para CRM |
| Panel admin | **Filament 3** | Genera el CRUD del CRM rápidamente |
| Base de datos | **MySQL** (XAMPP) | Ya disponible en el entorno |
| Formato app | **Web + PWA instalable** | Se ve/usa como programa de PC, pero centralizado (multi-rol, WhatsApp, web) |
| Roles | **Admin / Vendedor / Soporte** | Separación de responsabilidades |
| Nivel WhatsApp | **Automáticos + bot básico + bandeja con handoff** | Atención semiautomática y cierre manual |

---

## 4. Stack técnico

| Capa | Tecnología | Notas |
|------|-----------|-------|
| Backend | Laravel 11, PHP 8.2+ | Framework principal |
| Panel/CRUD | Filament 3 | Recursos de clientes, inventario, ventas, etc. |
| Roles y permisos | spatie/laravel-permission | Admin/Vendedor/Soporte |
| Base de datos | MySQL (XAMPP en dev) | Migraciones de Laravel |
| Colas | Laravel Queue (database/redis) | Envío de WhatsApp con ritmo y reintentos |
| Tareas programadas | Laravel Scheduler (cron) | Recordatorios de vencimiento |
| WhatsApp | Cloud API (Graph API de Meta) | Cliente HTTP propio (servicio Laravel) |
| Frontend | Blade + Livewire (incluido en Filament) | Bandeja de chat en tiempo real (Livewire/polling/WebSockets) |
| PWA | Manifest + Service Worker | Instalable como app de escritorio |
| IA (opcional, fase 4) | Claude API | Bot en lenguaje natural |
| Túnel dev | ngrok | Exponer webhooks de Meta hacia XAMPP local |

---

## 5. Arquitectura general

```
┌─────────────────────────────────────────────────────────────┐
│                        RebootCRM (Laravel)                    │
│                                                               │
│  ┌────────────┐   ┌──────────────┐   ┌────────────────────┐   │
│  │  Panel     │   │  API REST    │   │  Webhook WhatsApp   │   │
│  │ (Filament) │   │ (web ventas) │   │  (entrante Meta)    │   │
│  └─────┬──────┘   └──────┬───────┘   └─────────┬──────────┘   │
│        │                 │                     │              │
│  ┌─────┴─────────────────┴─────────────────────┴──────────┐   │
│  │                  Lógica de negocio                      │   │
│  │  (Clientes, Inventario, Ventas, Suscripciones, Chat)    │   │
│  └─────┬───────────────────────────────────┬──────────────┘   │
│        │                                   │                  │
│  ┌─────┴──────┐                  ┌──────────┴───────────┐      │
│  │   MySQL     │                  │  WhatsApp Service     │      │
│  │             │                  │  (envío vía Cloud API)│      │
│  └─────────────┘                  └──────────┬───────────┘      │
│                                              │                  │
│  ┌─────────────────┐   ┌──────────────────┐  │                  │
│  │ Scheduler (cron)│──▶│  Queue (jobs)     │──┘                  │
│  │ avisos venc.    │   │  envío con ritmo  │                     │
│  └─────────────────┘   └──────────────────┘                     │
└─────────────────────────────────────────────────────────────┘
                 ▲                         │
                 │ webhooks                │ mensajes salientes
                 │                         ▼
            ┌────────────────────────────────────┐
            │      WhatsApp Cloud API (Meta)       │
            │      Multi-número (varios WABA #)    │
            └────────────────────────────────────┘
```

---

## 6. Modelo de datos

Entidades principales (tablas):

- **users** — usuarios del sistema (con rol asignado).
- **roles / permissions** — vía spatie (Admin, Vendedor, Soporte).
- **customers** — clientes: nombre, teléfono/WhatsApp, notas, vendedor asignado.
- **platforms** — plataformas de streaming: nombre, tipo, precio base.
- **accounts** — inventario de cuentas: plataforma, email, contraseña (cifrada),
  nº de perfiles, estado (activa/vencida/bloqueada), fecha de compra/costo.
- **profiles** — perfiles dentro de una cuenta: nombre, PIN, estado (libre/asignado).
- **sales** — ventas: cliente, vendedor, ítems, total, fecha, estado.
- **sale_items** — detalle de la venta (qué perfil/plataforma, precio).
- **payments** — pagos: venta, monto, método, fecha, estado (pagado/pendiente/deuda).
- **subscriptions** — servicio vendido activo: cliente, perfil, fecha inicio,
  **fecha de vencimiento**, estado, recordatorios enviados.
- **whatsapp_numbers** — números conectados: phone_number_id, token, etiqueta, estado.
- **conversations** — hilo de chat por cliente/número: estado (bot/humano/cerrada),
  agente asignado.
- **messages** — mensajes entrantes/salientes: dirección, contenido, tipo, timestamp.
- **bot_flows / faqs** — respuestas del bot: catálogo, precios, dudas frecuentes.
- **message_templates** — plantillas aprobadas por Meta (recordatorios, bienvenida).

Relaciones clave:
- account 1—N profiles; profile 1—1 subscription (activa); customer 1—N subscriptions.
- customer 1—N sales; sale 1—N sale_items; sale 1—N payments.
- customer 1—N conversations; conversation 1—N messages.

---

## 7. Roles y permisos

| Rol | Puede | No puede |
|-----|-------|----------|
| **Admin** | Todo: usuarios, inventario, ventas, pagos, config WhatsApp, reportes y ganancias globales | — |
| **Vendedor** | Gestionar sus clientes y ventas, ver inventario disponible, atender chats asignados | Config del sistema, ver ganancias globales, gestionar usuarios |
| **Soporte** | Atender chats de soporte, ver datos del cliente y su suscripción, escalar a admin | Gestionar inventario, precios, ventas o usuarios |

Cada rol ve un panel/menú filtrado según sus permisos.

---

## 8. Integración WhatsApp (Cloud API)

### 8.1 Multi-número sin ban
- Cada número se registra en la **WhatsApp Business Account (WABA)** de Meta y obtiene
  un `phone_number_id`.
- El CRM guarda los números en `whatsapp_numbers` y enruta cada envío al número correcto.
- Al ser API oficial, la automatización está **permitida** → sin riesgo de ban.

### 8.2 Mensajes salientes
- Encolados (Laravel Queue) con delays para un ritmo natural.
- **Plantillas aprobadas** para iniciar conversación fuera de la ventana de 24h
  (ej. recordatorio de vencimiento, confirmación de venta).
- Dentro de la ventana de 24h tras un mensaje del cliente → respuestas libres.

### 8.3 Webhook entrante
- Endpoint público recibe mensajes de Meta (verificación + recepción).
- Identifica el `phone_number_id` para saber a qué número llegó.
- Crea/actualiza la conversación y dispara el bot o notifica al agente.

### 8.4 Automatizaciones
- **Venta concretada** → mensaje con datos de la cuenta/perfil.
- **Recordatorio de vencimiento** → ej. 3 días antes y el día del vencimiento
  (vía Scheduler que revisa `subscriptions`).

---

## 9. Bot de atención y bandeja de chat

- **Bot** responde automáticamente: catálogo, precios, dudas/soporte frecuente,
  flujo simple de toma de pedido (menú por opciones).
- **"Hablar con un humano"** → cambia el estado de la conversación a `humano` y la
  asigna a un vendedor/soporte, que responde desde la **bandeja de chat** del CRM.
- La bandeja muestra conversaciones de **todos los números** en un solo lugar, con
  estado (bot / humano / cerrada), asignación de agente y respuestas rápidas.

---

## 10. Integración futura con web de ventas

- El CRM expone una **API REST** (autenticada con token) para que la web de ventas:
  - Cree clientes y pedidos automáticamente tras una compra.
  - Dispare el mensaje de WhatsApp con los datos de la cuenta.
  - Consulte stock disponible.
- Se diseña el modelo de datos y la capa de servicios para soportar esta integración
  desde el inicio, aunque la web se conecte en una fase posterior.

---

## 11. Requisitos y consideraciones externas

- **Cuenta de Meta Business** + **WABA verificada** + número(s) registrados.
- Los **webhooks necesitan URL pública**: en desarrollo, **ngrok** hacia XAMPP; en
  producción, un hosting/VPS con HTTPS.
- **Costos Cloud API:** modelo por conversación (un tramo gratuito mensual y luego
  costo por conversación); a validar según país.
- **Seguridad:** contraseñas de cuentas cifradas en BD; tokens de WhatsApp fuera del
  repositorio (.env); HTTPS obligatorio en producción.

---

## 12. Roadmap por fases

### Fase 1 — MVP CRM (sin WhatsApp)
- Laravel + Filament + MySQL + spatie/permission.
- Usuarios y roles (Admin/Vendedor/Soporte).
- Clientes, plataformas, inventario de cuentas y perfiles.
- Ventas, pagos y suscripciones con vencimiento.
- PWA instalable.
- **Entregable:** CRM funcional para gestionar el negocio manualmente.

### Fase 2 — WhatsApp saliente
- Conexión Cloud API multi-número.
- Plantillas, cola de envío, mensaje de venta y recordatorios de vencimiento.

### Fase 3 — Bandeja + bot entrante
- Webhook, bot de dudas/soporte/catálogo, "hablar con un humano", asignación.

### Fase 4 — Integración web + extras (opcional)
- API para la web de ventas, reportes avanzados, IA con Claude.

---

## 13. Criterios de éxito (Fase 1)

- Un admin puede crear usuarios con rol y cada rol ve solo lo que le corresponde.
- Se puede registrar inventario (cuentas/perfiles) y ver qué está libre/ocupado.
- Se puede registrar una venta con su pago y generar la suscripción con vencimiento.
- El sistema muestra suscripciones próximas a vencer.
- La app se puede instalar en el escritorio como PWA.

---

_Próximo paso: plan de implementación detallado de la Fase 1._
