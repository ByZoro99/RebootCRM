<?php

namespace App\Console\Commands;

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
            try {
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
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->info("Recordatorios enviados: {$sent}");
        return self::SUCCESS;
    }
}
