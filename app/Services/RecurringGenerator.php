<?php

namespace App\Services;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;

/** Cria os lançamentos que as recorrências ativas já deveriam ter gerado até hoje. */
class RecurringGenerator
{
    /**
     * Gera as ocorrências pendentes e devolve quantos lançamentos foram criados.
     * Com alguém logado só mexe nas recorrências dele; no console, em todas.
     */
    public function run(?CarbonInterface $today = null): int
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();
        $created = 0;

        RecurringTransaction::query()
            ->where('active', true)
            ->whereDate('starts_on', '<=', $today->toDateString())
            ->get()
            ->each(function (RecurringTransaction $rule) use ($today, &$created) {
                $from = $rule->generated_until?->addDay() ?? $rule->starts_on;

                foreach ($rule->occurrences($from, $today) as $date) {
                    $created += $this->create($rule, $date) ? 1 : 0;
                }

                $rule->forceFill(['generated_until' => $today])->saveQuietly();
            });

        return $created;
    }

    private function create(RecurringTransaction $rule, CarbonImmutable $date): bool
    {
        $transaction = new Transaction([
            'description' => $rule->description,
            'amount' => $rule->amount,
            'date' => $date,
            'category_id' => $rule->category_id,
            'account_id' => $rule->account_id,
            'credit_card_id' => $rule->credit_card_id,
        ]);
        $transaction->user_id = $rule->user_id;
        $transaction->recurring_transaction_id = $rule->id;

        try {
            $transaction->save();
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }
}
