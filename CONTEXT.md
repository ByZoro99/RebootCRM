# CRM — Contexto y Memoria del Proyecto

> Este archivo mantiene el contexto, las decisiones y el estado del proyecto CRM.
> Se actualiza a medida que avanzamos. El historial detallado de cambios está en `CHANGELOG.md`.

---

## 1. Resumen del proyecto

- **Nombre:** RebootCRM
- **Ubicación:** `c:\xampp\htdocs\CRM`
- **Entorno:** XAMPP (Apache + MySQL + PHP) en Windows
- **Estado:** 🟢 Diseño aprobado — listo para plan de implementación (Fase 1)
- **Repositorio git:** rama `main` — remoto: https://github.com/ByZoro99/RebootCRM
- **Spec de diseño:** [docs/specs/2026-06-13-rebootcrm-design.md](docs/specs/2026-06-13-rebootcrm-design.md)

## 2. Objetivo

CRM para gestionar la **venta de cuentas de streaming** (Netflix, Disney+, Spotify…):
clientes, inventario de cuentas/perfiles, ventas/pagos y suscripciones con vencimiento.
Integra **WhatsApp oficial (Cloud API)** multi-número **sin riesgo de ban** para
automatizar comunicaciones, con un **bot de atención** y derivación a humano. Pensado
para conectarse a una **web de ventas** en el futuro.

Usuarios: **Admin** (control total), **Vendedor** y **Soporte** (con permisos propios).

## 3. Stack técnico

| Capa            | Tecnología                   | Notas                             |
|-----------------|------------------------------|-----------------------------------|
| Backend         | **Laravel 11 (PHP 8.2+)**    | Framework principal               |
| Panel/CRUD      | **Filament 3**               | Genera el CRUD del CRM            |
| Roles/permisos  | **spatie/laravel-permission**| Admin / Vendedor / Soporte        |
| Base de datos   | **MySQL** (XAMPP)            | Migraciones de Laravel            |
| WhatsApp        | **Cloud API (Meta)**         | Oficial, multi-número, sin ban    |
| Colas/tareas    | Laravel Queue + Scheduler    | Envíos con ritmo y avisos venc.   |
| Frontend        | Blade + Livewire + **PWA**   | Instalable como app de escritorio |
| Túnel dev       | ngrok                        | Webhooks de Meta hacia XAMPP      |

## 4. Estructura de carpetas

```
CRM/
├── CONTEXT.md      ← este archivo (contexto y memoria)
├── CHANGELOG.md    ← historial de versiones/cambios
├── .gitignore
└── docs/
    └── specs/
        └── 2026-06-13-rebootcrm-design.md  ← spec de diseño aprobada
```

## 5. Decisiones clave

| Fecha       | Decisión                                   | Motivo |
|-------------|--------------------------------------------|--------|
| 2026-06-13  | Inicializar git + archivo de contexto      | Control de versiones y memoria del proyecto |
| 2026-06-13  | WhatsApp **Cloud API oficial**             | Único camino sin riesgo de ban + multi-número |
| 2026-06-13  | Stack **Laravel + Filament + MySQL**       | Robusto y aprovecha experiencia PHP/XAMPP |
| 2026-06-13  | App **web + PWA instalable**               | Se usa como programa de PC pero centralizado |
| 2026-06-13  | Roles **Admin / Vendedor / Soporte**       | Separación de responsabilidades |

## 6. Tareas / Roadmap

- [x] Definir objetivo y alcance del CRM
- [x] Elegir stack (Laravel + Filament + MySQL)
- [x] Diseñar el modelo de datos (entidades principales) → en la spec
- [ ] Plan de implementación de la Fase 1
- [ ] **Fase 1:** MVP CRM (usuarios/roles, clientes, inventario, ventas/pagos, suscripciones, PWA)
- [ ] **Fase 2:** WhatsApp saliente (Cloud API multi-número, recordatorios)
- [ ] **Fase 3:** Bandeja de chat + bot con "hablar con un humano"
- [ ] **Fase 4:** Integración con web de ventas + extras (IA opcional)

## 7. Notas y pendientes

> _(Apuntes sueltos, dudas, ideas)_

---

_Última actualización: 2026-06-13_
