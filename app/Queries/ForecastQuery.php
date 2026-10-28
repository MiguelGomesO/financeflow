<?php

namespace App\Queries;

use App\Models\Account;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Previsão do saldo das contas até o fim do mês: o saldo de hoje mais o que ainda vai
 * acontecer — lançamentos já cadastrados com data futura e ocorrências das recorrências
 * que o gerador ainda não criou. Recorrentes no cartão não mexem no saldo (vão para a fatura).
 */
class ForecastQuery
{
    private CarbonImmutable $today;

    public function __construct(CarbonInterface $today)
    {
        $this->today = CarbonImmutable::instance($today)->startOfDay();
    }

    /**
     * @return array{
     *     end: CarbonImmutable,
     *     balance: float,
     *     income: float,
     *     expense: float,
     *     projected: float,
     *     card: float,
     *     lowest: array{amount: float, date: CarbonImmutable},
     *     events: list<array{date: CarbonImmutable, description: string, amount: float, recurring: bool}>
     * }
     */
    public function month(): array
    {
        $end = $this->today->endOfMonth()->startOfDay();
        $tomorrow = $this->today->addDay();

        $events = $tomorrow->lessThanOrEqualTo($end) ? $this->events($tomorrow, $end) : [];

        // Saldo de hoje: o app soma tudo que está cadastrado; aqui fica de fora o que ainda vai acontecer.
        $balance = round(
            Account::query()->withBalance()->get()->sum('balance')
            - (float) Transaction::query()->whereNotNull('account_id')->where('date', '>', $this->today->toDateString())->sum('amount'),
            2,
        );

        $income = round(array_sum(array_filter(array_column($events, 'amount'), fn (float $a) => $a > 0)), 2);
        $expense = round(-array_sum(array_filter(array_column($events, 'amount'), fn (float $a) => $a < 0)), 2);

        return [
            'end' => $end,
            'balance' => $balance,
            'income' => $income,
            'expense' => $expense,
            'projected' => round($balance + $income - $expense, 2),
            'card' => $this->cardExpenses($tomorrow, $end),
            'lowest' => $this->lowestPoint($balance, $events),
            'events' => $events,
        ];
    }

    /** Lançamentos futuros das contas até o fim do mês, em ordem de data. Na mesma data, saídas antes das entradas. */
    private function events(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $events = Transaction::query()
            ->whereNotNull('account_id')
            ->betweenDates($from, $to)
            ->get(['date', 'description', 'amount'])
            ->map(fn (Transaction $t) => [
                'date' => CarbonImmutable::instance($t->date),
                'description' => $t->description,
                'amount' => $t->amount,
                'recurring' => false,
            ])
            ->all();

        RecurringTransaction::query()
            ->where('active', true)
            ->whereNotNull('account_id')
            ->get()
            ->each(function (RecurringTransaction $rule) use ($from, $to, &$events) {
                foreach ($rule->occurrences($from, $to) as $date) {
                    $events[] = ['date' => $date, 'description' => $rule->description, 'amount' => $rule->amount, 'recurring' => true];
                }
            });

        usort($events, fn (array $a, array $b) => [$a['date']->timestamp, $a['amount']] <=> [$b['date']->timestamp, $b['amount']]);

        return $events;
    }

    /** Quanto as recorrências no cartão ainda vão somar às faturas até o fim do mês. */
    private function cardExpenses(CarbonImmutable $from, CarbonImmutable $to): float
    {
        if ($from->greaterThan($to)) {
            return 0.0;
        }

        $total = 0.0;

        RecurringTransaction::query()
            ->where('active', true)
            ->whereNotNull('credit_card_id')
            ->get()
            ->each(function (RecurringTransaction $rule) use ($from, $to, &$total) {
                $total += -$rule->amount * count($rule->occurrences($from, $to));
            });

        return round($total, 2);
    }

    /** Menor saldo ao longo do mês (começando pelo de hoje) e o dia em que acontece. */
    private function lowestPoint(float $balance, array $events): array
    {
        $lowest = ['amount' => $balance, 'date' => $this->today];
        $running = $balance;

        foreach ($events as $event) {
            $running = round($running + $event['amount'], 2);

            if ($running < $lowest['amount']) {
                $lowest = ['amount' => $running, 'date' => $event['date']];
            }
        }

        return $lowest;
    }
}
