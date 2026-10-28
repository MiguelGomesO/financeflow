<?php

namespace App\Console\Commands;

use App\Services\BudgetAlerter;
use Illuminate\Console\Command;

class SendBudgetAlerts extends Command
{
    protected $signature = 'finance:budget-alerts';

    protected $description = 'Envia e-mail dos orçamentos que chegaram a 80% ou 100% do limite (roda de hora em hora pelo agendador)';

    public function handle(BudgetAlerter $alerter): int
    {
        $this->info($alerter->run().' e-mail(s) enviado(s).');

        return self::SUCCESS;
    }
}
