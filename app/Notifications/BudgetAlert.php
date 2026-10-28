<?php

namespace App\Notifications;

use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** E-mail com os orçamentos que acabaram de chegar a 80% ou 100% do limite. */
class BudgetAlert extends Notification
{
    /** @param  list<array{name: string, spent: float, limit: float, level: int}>  $alerts */
    public function __construct(private array $alerts) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reached = collect($this->alerts)->contains(fn (array $a) => $a['level'] >= 100);

        $mail = (new MailMessage)
            ->subject($reached ? 'Você atingiu o limite de um orçamento' : 'Um orçamento está perto do limite')
            ->greeting('Olá, '.$notifiable->name.'!');

        foreach ($this->alerts as $alert) {
            $verb = $alert['level'] >= 100 ? 'atingiu o limite' : 'chegou a 80% do limite';

            $mail->line(sprintf(
                '%s %s: %s de %s.',
                $alert['name'],
                $verb,
                Money::brl($alert['spent']),
                Money::brl($alert['limit']),
            ));
        }

        return $mail
            ->action('Ver orçamentos', route('budgets'))
            ->line('Você pode desligar estes avisos no seu perfil.');
    }
}
