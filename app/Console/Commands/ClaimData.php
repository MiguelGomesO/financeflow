<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClaimData extends Command
{
    protected $signature = 'finance:claim {email : E-mail do usuário que ficará com os dados sem dono}';

    protected $description = 'Entrega a um usuário os registros criados antes do login existir (sem user_id)';

    private const TABLES = ['accounts', 'credit_cards', 'transactions', 'budgets', 'goals', 'goal_deposits', 'transfers'];

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('Nenhum usuário com esse e-mail. Crie a conta no app primeiro.');

            return self::FAILURE;
        }

        foreach (self::TABLES as $table) {
            $count = DB::table($table)->whereNull('user_id')->update(['user_id' => $user->id]);
            $this->line(str_pad($table, 15)." {$count}");
        }

        $this->info("Dados entregues a {$user->email}.");

        return self::SUCCESS;
    }
}
