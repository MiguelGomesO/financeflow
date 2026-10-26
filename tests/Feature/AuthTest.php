<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected bool $signIn = false;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        foreach (['dashboard', 'transactions', 'budgets', 'goals', 'accounts'] as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }
    }

    public function test_login_and_register_pages_render_for_guests(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Entrar');
        $this->get(route('register'))->assertOk()->assertSee('Criar conta');
    }

    public function test_logged_in_users_skip_the_login_page(): void
    {
        $this->actingAs(User::factory()->create())->get(route('login'))->assertRedirect();
    }

    public function test_logs_in_with_the_right_password(): void
    {
        $user = User::factory()->create(['password' => 'segredo123']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'segredo123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_rejects_a_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'segredo123']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'errada')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_blocks_after_too_many_failed_attempts(): void
    {
        $user = User::factory()->create(['password' => 'segredo123']);
        $login = Livewire::test(Login::class)->set('email', $user->email);

        foreach (range(1, 5) as $_) {
            $login->set('password', 'errada')->call('login');
        }

        $login->set('password', 'segredo123')->call('login')->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_registers_and_signs_in(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Maria')
            ->set('email', 'maria@example.com')
            ->set('password', 'segredo123')
            ->set('passwordConfirmation', 'segredo123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user = User::where('email', 'maria@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame('segredo123', $user->password);
    }

    public function test_register_validates_input(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);

        Livewire::test(Register::class)
            ->set('name', '')
            ->set('email', 'maria@example.com')
            ->set('password', 'curta')
            ->set('passwordConfirmation', 'outra')
            ->call('register')
            ->assertHasErrors(['name', 'email', 'password']);

        $this->assertGuest();
    }

    public function test_logs_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
