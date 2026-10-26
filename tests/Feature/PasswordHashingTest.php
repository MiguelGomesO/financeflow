<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordHashingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $signIn = false;

    public function test_passwords_are_hashed_with_argon2id(): void
    {
        $user = User::factory()->create(['password' => 'segredo123']);

        $this->assertStringStartsWith('$argon2id$', $user->password);
        $this->assertTrue(Hash::check('segredo123', $user->password));
        $this->assertFalse(Hash::check('outra', $user->password));
    }

    public function test_registration_stores_an_argon2id_hash(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Maria')
            ->set('email', 'maria@example.com')
            ->set('password', 'segredo123')
            ->set('passwordConfirmation', 'segredo123')
            ->call('register');

        $this->assertStringStartsWith('$argon2id$', User::where('email', 'maria@example.com')->value('password'));
    }

    public function test_old_bcrypt_passwords_still_log_in_and_are_upgraded(): void
    {
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update(['password' => password_hash('segredo123', PASSWORD_BCRYPT)]);

        $this->assertStringStartsWith('$2y$', $user->fresh()->password);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'segredo123')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertStringStartsWith('$argon2id$', $user->fresh()->password);
    }
}
