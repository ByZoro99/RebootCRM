<?php

return [
    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'rebootcrm-verify'),
    'templates' => [
        'delivery' => env('WHATSAPP_TPL_DELIVERY', 'entrega_cuenta'),
        'reminder' => env('WHATSAPP_TPL_REMINDER', 'recordatorio_vencimiento'),
    ],
    'template_lang' => env('WHATSAPP_TPL_LANG', 'es'),
    'reminder_days' => (int) env('WHATSAPP_REMINDER_DAYS', 3),
];
