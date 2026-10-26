<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lança as recorrências que venceram. Em produção, o cron chama `php artisan schedule:run` a cada minuto.
Schedule::command('finance:recurring')->daily();
