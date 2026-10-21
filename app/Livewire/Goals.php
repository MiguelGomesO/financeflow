<?php

namespace App\Livewire;

use App\Livewire\Forms\DepositForm;
use App\Livewire\Forms\GoalForm;
use App\Support\GoalProgress;
use App\Support\SampleData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Metas · FinanceFlow')]
class Goals extends Component
{
    public GoalForm $form;

    public DepositForm $deposit;

    /** Por enquanto metas e depósitos vivem no estado do componente (sem banco). */
    public array $goals = [];

    public array $deposits = [];

    /** Meta aberta no modal de edição (null = criando uma nova). */
    public ?int $editingId = null;

    /** Meta que vai receber o depósito. */
    public ?int $depositGoalId = null;

    public function mount(): void
    {
        $this->goals = SampleData::goals();
        $this->deposits = SampleData::goalDeposits();
    }

    public function create(): void
    {
        $this->editingId = null;
        $this->form->clear();
        $this->form->due = SampleData::today()->addYear()->format('Y-m');

        $this->dispatch('open-modal', name: 'goal-form');
    }

    public function edit(int $id): void
    {
        $goal = $this->findGoal($id);

        if ($goal === null) {
            return;
        }

        $this->editingId = $id;
        $this->form->setGoal($goal);

        $this->dispatch('open-modal', name: 'goal-form');
    }

    public function saveGoal(): void
    {
        if ($this->editingId === null) {
            $this->goals[] = ['id' => $this->nextId($this->goals)] + $this->form->validated(creating: true);
        } else {
            $index = $this->goalIndex($this->editingId);
            $this->goals[$index] = array_merge($this->goals[$index], $this->form->validated(creating: false));
        }

        $this->closeGoalForm();
    }

    public function deleteGoal(): void
    {
        $id = $this->editingId;

        $this->goals = array_values(array_filter($this->goals, fn (array $g) => $g['id'] !== $id));
        $this->deposits = array_values(array_filter($this->deposits, fn (array $d) => $d['goal_id'] !== $id));

        $this->closeGoalForm();
    }

    public function startDeposit(int $id): void
    {
        if ($this->findGoal($id) === null) {
            return;
        }

        $this->depositGoalId = $id;
        $this->deposit->clear();

        $this->dispatch('open-modal', name: 'deposit');
    }

    public function saveDeposit(): void
    {
        $index = $this->goalIndex($this->depositGoalId);

        if ($index === null) {
            return;
        }

        $data = $this->deposit->validated();

        $this->goals[$index]['saved'] += $data['amount'];

        array_unshift($this->deposits, [
            'id' => $this->nextId($this->deposits),
            'goal_id' => $this->depositGoalId,
            'date' => SampleData::TODAY,
            'account' => $data['account'],
            'amount' => $data['amount'],
        ]);

        $this->deposit->clear();
        $this->depositGoalId = null;

        $this->dispatch('close-modal', name: 'deposit');
    }

    public function render()
    {
        $currentMonth = SampleData::currentMonth();

        $goals = array_map(
            fn (array $goal) => $goal + ['progress' => GoalProgress::for($goal, $currentMonth)],
            $this->goals,
        );

        $names = array_column($this->goals, 'name', 'id');

        $deposits = collect($this->deposits)
            ->filter(fn (array $d) => isset($names[$d['goal_id']]))
            ->map(fn (array $d) => $d + ['goal' => $names[$d['goal_id']]])
            ->sortBy([['date', 'desc'], ['id', 'desc']])
            ->values()
            ->all();

        return view('livewire.goals', [
            'items' => $goals,
            'depositRows' => $deposits,
            'editing' => $this->editingId !== null,
            'depositGoal' => $this->depositGoalId ? $this->findGoal($this->depositGoalId) : null,
            'accounts' => DepositForm::ACCOUNTS,
            'totals' => [
                'saved' => array_sum(array_column($this->goals, 'saved')),
                'remaining' => array_sum(array_map(fn (array $g) => $g['progress']['remaining'], $goals)),
                'monthly' => array_sum(array_map(fn (array $g) => $g['progress']['monthly'], $goals)),
            ],
        ]);
    }

    private function closeGoalForm(): void
    {
        $this->form->clear();
        $this->editingId = null;

        $this->dispatch('close-modal', name: 'goal-form');
    }

    private function findGoal(int $id): ?array
    {
        $index = $this->goalIndex($id);

        return $index === null ? null : $this->goals[$index];
    }

    private function goalIndex(?int $id): ?int
    {
        foreach ($this->goals as $index => $goal) {
            if ($goal['id'] === $id) {
                return $index;
            }
        }

        return null;
    }

    private function nextId(array $rows): int
    {
        return max(array_column($rows, 'id') ?: [0]) + 1;
    }
}
