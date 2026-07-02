# Guía: Crear WhatsApp Cloud API (Meta) desde cero

> Esta guía es para **ti** (ByZoro99). Sigue los pasos en orden. Algunas partes tardan
> (verificación de Meta puede llevar de horas a varios días), por eso conviene empezar YA.
> Cuando termines, me pasas los **4 datos** del final y yo conecto el CRM.

---

## ⚠️ Antes de empezar: lee esto

1. **Necesitas un número de teléfono DEDICADO** que **NO** esté registrado actualmente en
   la app de WhatsApp ni en WhatsApp Business (la app del celular). Si tu número de ventas
   está en la app de WhatsApp Business, tienes 2 opciones:
   - Usar un **número nuevo** solo para la API (recomendado), o
   - **Borrar** ese número de la app de WhatsApp Business para migrarlo a la API (pierdes el uso en la app).
   > Puede ser un número físico (SIM) o virtual que pueda recibir un SMS/llamada de verificación.

2. Necesitas una cuenta de **Facebook** personal para crear el Business Manager.

3. El **plan gratuito** de la Cloud API te da mensajes de servicio gratis cada mes y
   luego cobra por conversación (barato). Para empezar y probar es gratis.

---

## Paso 1 — Crear cuenta de Meta Business

1. Entra a 👉 **https://business.facebook.com/**
2. Clic en **Crear cuenta**.
3. Rellena: nombre del negocio (ej. "RebootStream"), tu nombre y tu email de trabajo.
4. Confirma el email que te llegue.

## Paso 2 — Crear la app de desarrollador

1. Entra a 👉 **https://developers.facebook.com/apps/**
2. Clic en **Crear app**.
3. Tipo de app: elige **"Empresa" / "Business"**.
4. Ponle nombre (ej. "RebootCRM WhatsApp"), asocia tu cuenta de Meta Business del Paso 1.
5. Crea la app.

## Paso 3 — Añadir el producto WhatsApp

1. Dentro de la app, en el panel, busca **"WhatsApp"** y clic en **Configurar / Set up**.
2. Te pedirá asociar tu **cuenta de WhatsApp Business (WABA)**; créala si no existe.
3. Meta te dará automáticamente un **número de prueba** gratuito. Sirve para probar antes
   de meter tu número real.

## Paso 4 — Obtener los datos de conexión

En la sección **WhatsApp → API Setup / Configuración de la API** verás:

- **Temporary access token (token de acceso temporal):** dura 24h. Para producción
  necesitarás uno permanente (Paso 6).
- **Phone number ID (identificador del número):** un número largo. **Cópialo.**
- **WhatsApp Business Account ID (WABA ID).**
- Un desplegable con el **número de prueba**.

En esa misma pantalla puedes **enviar un mensaje de prueba** a tu propio celular para
confirmar que todo funciona. Hazlo: agrega tu número personal como destinatario de prueba
y envía el "hello_world".

## Paso 5 — Registrar tu número real (cuando estés listo)

1. En **WhatsApp → Configuración → Números de teléfono**, clic en **Agregar número**.
2. Ingresa el número dedicado (Paso previo), elige verificación por **SMS o llamada**.
3. Introduce el código que recibas.
4. Ese número queda listo para enviar/recibir por la API.

> Para enviar mensajes a clientes que NO te han escrito primero, Meta exige **plantillas
> aprobadas** (ver Paso 7).

## Paso 6 — Token permanente (para producción)

El token temporal caduca en 24h. Para el CRM en producción necesitas uno permanente:

1. Ve a **https://business.facebook.com/settings/system-users**
2. Crea un **System User** (usuario del sistema) tipo **Admin**.
3. Clic en **Generar token**, elige tu app, y en permisos marca:
   `whatsapp_business_messaging` y `whatsapp_business_management`.
4. Genera y **guarda el token** (no se vuelve a mostrar). Este es el que usará el CRM.

## Paso 7 — Crear plantillas de mensajes (templates)

Para los mensajes automáticos (datos de la cuenta, recordatorio de vencimiento) necesitas
plantillas aprobadas por Meta:

1. Ve a 👉 **https://business.facebook.com/wa/manage/message-templates/**
2. Crea plantillas. Sugeridas para el CRM (categoría **Utility/Utilidad**):

   **a) `entrega_cuenta`** (envío de datos tras la compra):
   ```
   ¡Hola {{1}}! Gracias por tu compra en RebootStream 🎬
   Estos son tus datos de acceso a {{2}}:
   Usuario: {{3}}
   Contraseña: {{4}}
   Perfil: {{5}}
   Tu suscripción vence el {{6}}. ¡Que lo disfrutes!
   ```

   **b) `recordatorio_vencimiento`** (aviso de renovación):
   ```
   Hola {{1}} 👋 Tu suscripción de {{2}} vence el {{3}}.
   ¿Deseas renovar? Responde a este mensaje y te ayudamos.
   ```
3. Envíalas a revisión. La aprobación suele tardar de minutos a unas horas.

---

## ✅ Qué necesito que me pases al final

Cuando tengas al menos el número de prueba funcionando, mándame estos **4 datos** (el token
va en un archivo seguro, nunca en el chat público si es el permanente — pero para pruebas
con el token temporal está bien):

1. **Phone number ID** (Paso 4)
2. **WABA ID** (Paso 4)
3. **Access token** (temporal para pruebas / permanente para producción, Paso 6)
4. El **número de teléfono** que usarás (con código de país, ej. `521551234...`)

Con eso configuro el `.env` del CRM y hacemos la primera prueba de envío real.

> Mientras tanto, yo dejo TODO el código listo para que, en cuanto pegues esos datos,
> funcione. Las pruebas internas las hago simulando la API (sin gastar mensajes).
