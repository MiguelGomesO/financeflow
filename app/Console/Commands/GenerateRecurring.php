<?php

namespace App\Console\Commands;

use App\Services\RecurringGenerator;
use Illuminate\Console\Command;

class GenerateRecurring extends Command
{
    protected $signature = 'finance:recurring';

    protected $description = 'Cria os lançamentos das recorrências que já venceram (roda todo dia pelo agendador)';

    public function handle(RecurringGenerator $generator): int
    {
        $this->info($generator->run().' lançamento(s) criado(s).');

        return self::SUCCESS;
    }
}
