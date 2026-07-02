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
     * @param array<int,string> $params
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
