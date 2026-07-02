# Changelog — CRM

Todos los cambios relevantes del proyecto se documentan aquí.
Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/).

## [Unreleased]

### Added
- Inicialización del repositorio git (rama `main`).
- `CONTEXT.md` — archivo de contexto y memoria del proyecto.
- `CHANGELOG.md` — historial de versiones.
- `.gitignore` para entorno PHP/XAMPP.
- Remoto en GitHub: https://github.com/ByZoro99/RebootCRM
- **Spec de diseño aprobada** (`docs/specs/2026-06-13-rebootcrm-design.md`): alcance,
  stack (Laravel + Filament + MySQL), WhatsApp Cloud API multi-número, roles
  (Admin/Vendedor/Soporte), bot con derivación a humano, PWA y roadmap por fases.
- **Plan de implementación Fase 1** (`docs/plans/2026-06-13-fase1-mvp-crm.md`).
- **Fase 1 · Task 0:** scaffold de **Laravel 12.62** sobre XAMPP (PHP 8.2). Composer
  instalado, extensiones PHP habilitadas (gd, zip, intl, sqlite3), base de datos
  `rebootcrm` en MariaDB, `.env` configurado, migraciones base aplicadas y tests OK.
  > Nota: se usa Laravel 12 (no 11 como decía el plan) porque Composer bloquea Laravel
  > 11 por advisories de seguridad. Filament 3 es compatible con Laravel 12.
- **Fase 1 completa:** MVP del CRM (roles admin/vendedor/soporte, plataformas, inventario
  de cuentas/perfiles con contraseña cifrada, clientes, ventas/pagos, suscripciones con
  vencimiento, dashboard, PWA instalable). 21 tests. Integrado a `main`.
- **Fase 2 completa (WhatsApp saliente):**
  - `config/whatsapp.php` + modelo `WhatsappNumber` (multi-número, token cifrado, recurso admin).
  - `WhatsAppService` (envío de texto y plantillas vía Cloud API) + log `whatsapp_messages`.
  - Envío automático de los datos de la cuenta por WhatsApp al concretar una venta (best-effort).
  - Comando `whatsapp:send-reminders` + scheduler diario (09:00) para recordatorios de vencimiento.
  - Guía `docs/guias/whatsapp-cloud-api-setup.md` para dar de alta la API en Meta.
  - 28 tests en total (Http::fake, sin llamadas reales). Integrado a `main`.

---

_Convención de versiones: [SemVer](https://semver.org/lang/es/) — MAYOR.MENOR.PARCHE_
