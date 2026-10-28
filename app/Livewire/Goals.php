<?php

namespace App\Livewire;

use App\Livewire\Forms\DepositForm;
use App\Livewire\Forms\GoalForm;
use App\Models\Account;
use App\Models\Goal;
use App\Models\GoalDeposit;
use App\Support\GoalProgress;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Metas · FinanceFlow')]
class Goals extends Component
{
    use WithPagination;

    public GoalForm $form;

    public DepositForm $deposit;

    /** Meta aberta no modal de edição (null = criando uma nova). */
    public ?int $editingId = null;

    /** Meta que vai receber o depósito. */
    public ?int $depositGoalId = null;

    public function create(): void
    {
        $this->editingId = null;
        $this->form->setDefaults();

        $this->dispatch('open-modal', name: 'goal-form');
    }

    public function edit(int $id): void
    {
        $goal = Goal::find($id);

        if ($goal === null) {
            return;
        }

        $this->editingId = $goal->id;
        $this->form->setGoal($goal);

        $this->dispatch('open-modal', name: 'goal-form');
    }

    public function saveGoal(): void
    {
        $creating = $this->editingId === null;
        $data = $this->form->validated($creating);
        $initial = $data['initial'];
        unset($data['initial']);

        DB::transaction(function () use ($creating, $data, $initial) {
            if (! $creating) {
                Goal::whereKey($this->editingId)->first()?->update($data);

                return;
            }

            $goal = Goal::create($data);

            if ($initial > 0) {
                $goal->deposits()->create(['amount' => $initial, 'date' => now()->toDateString()]);
            }
        });

        $this->closeGoalForm();
    }

    /** Exclui a meta; os depósitos dela saem junto (cascade). */
    public function deleteGoal(): void
    {
        Goal::whereKey($this->editingId)->delete();

        $this->closeGoalForm();
    }

    public function startDeposit(int $id): void
    {
        if (! Goal::whereKey($id)->exists()) {
            return;
        }

        $this->depositGoalId = $id;
        $this->deposit->setDefaults(
            GoalDeposit::query()->whereNotNull('account_id')->latest('id')->value('account_id')
                ?? Account::query()->orderBy('id')->value('id')
        );

        $this->dispatch('open-modal', name: 'deposit');
    }

    public function saveDeposit(): void
    {
        $goal = Goal::find($this->depositGoalId);

        if ($goal === null) {
            return;
        }

        $goal->deposits()->create($this->deposit->validated());

        $this->depositGoalId = null;
        $this->deposit->reset();

        $this->dispatch('close-modal', name: 'deposit');
    }

    public function deleteDeposit(int $id): void
    {
        GoalDeposit::whereKey($id)->delete();
    }

    public function render()
    {
        $currentMonth = now()->format('Y-m');

        $goals = Goal::query()
            ->withSum('deposits', 'amount')
            ->orderBy('due_month')
            ->orderBy('id')
            ->get()
            ->map(function (Goal $goal) use ($currentMonth) {
                $item = [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'saved' => $goal->saved,
                    'target' => $goal->target,
                    'due' => $goal->due_month,
                ];

                return $item + ['progress' => GoalProgress::for($item, $currentMonth)];
            });

        $deposits = GoalDeposit::query()
            ->with(['goal:id,name', 'account:id,name'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(10)
            ->through(fn (GoalDeposit $d) => [
                'id' => $d->id,
                'date' => $d->date->toDateString(),
                'goal' => $d->goal->name,
                'account' => $d->account?->name,
                'amount' => $d->amount,
            ]);

        return view('livewire.goals', [
            'items' => $goals->all(),
            'depositRows' => $deposits,
            'editing' => $this->editingId !== null,
            'depositGoal' => $this->depositGoalId ? $goals->firstWhere('id', $this->depositGoalId) : null,
            'accounts' => Account::query()->orderBy('id')->get(['id', 'name']),
            'totals' => [
                'saved' => $goals->sum('saved'),
                'remaining' => $goals->sum(fn (array $g) => $g['progress']['remaining']),
                'monthly' => $goals->sum(fn (array $g) => $g['progress']['monthly']),
            ],
        ]);
    }

    private function closeGoalForm(): void
    {
        $this->form->clear();
        $this->editingId = null;

        $this->dispatch('close-modal', name: 'goal-form');
    }
}
