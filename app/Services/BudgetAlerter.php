<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\User;
use App\Notifications\BudgetAlert;
use App\Support\BudgetStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;

/**
 * Avisa por e-mail quando um orçamento do mês chega a 80% ou a 100% do limite.
 * Cada faixa é avisada uma vez: `alert_level` guarda a última já enviada.
 */
class BudgetAlerter
{
    /** Devolve quantos e-mails foram enviados. */
    public function run(?CarbonInterface $today = null): int
    {
        $month = ($today ?? now())->format('Y-m');
        $sent = 0;
        $previous = Auth::user();

        try {
            User::query()->each(function (User $user) use ($month, &$sent) {
                // Os orçamentos e os gastos só enxergam o usuário logado, então "vira" cada um por vez.
                Auth::setUser($user);

                $alerts = $this->crossed($month);

                if ($alerts !== [] && $user->budget_alerts) {
                    $user->notify(new BudgetAlert($alerts));
                    $sent++;
                }
            });
        } finally {
            $previous ? Auth::setUser($previous) : Auth::forgetUser();
        }

        return $sent;
    }

    /**
     * Atualiza o nível de cada orçamento do mês e devolve os que subiram de faixa.
     * Se o gasto caiu (transação editada ou excluída), o nível desce e o aviso volta a valer.
     */
    private function crossed(string $month): array
    {
        $alerts = [];

        Budget::query()
            ->with('category:id,name')
            ->withSpent($month)
            ->where('month', $month)
            ->get()
            ->each(function (Budget $budget) use (&$alerts) {
                $level = BudgetStatus::alertLevel((float) $budget->spent, $budget->limit);

                if ($level === $budget->alert_level) {
                    return;
                }

                if ($level > $budget->alert_level) {
                    $alerts[] = [
                        'name' => $budget->category->name,
                        'spent' => (float) $budget->spent,
                        'limit' => $budget->limit,
                        'level' => $level,
                    ];
                }

                $budget->forceFill(['alert_level' => $level])->saveQuietly();
            });

        return $alerts;
    }
}
