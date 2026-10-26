<?php

namespace Tests\Feature;

use App\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_menu_offers_edit_profile_and_logout(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Editar perfil')
            ->assertSee('Sair')
            ->assertSee(route('profile'));
    }

    public function test_updates_name_and_email(): void
    {
        Livewire::test(Profile::class)
            ->set('name', 'Novo Nome')
            ->set('email', 'novo@example.com')
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->assertRedirect(route('profile'));

        $this->assertSame('Novo Nome', $this->user->fresh()->name);
        $this->assertSame('novo@example.com', $this->user->fresh()->email);
    }

    public function test_email_must_be_unique_but_can_stay_the_same(): void
    {
        User::factory()->create(['email' => 'outra@example.com']);

        Livewire::test(Profile::class)
            ->set('email', 'outra@example.com')
            ->call('saveProfile')
            ->assertHasErrors('email');

        Livewire::test(Profile::class)
            ->set('name', 'Mesmo Email')
            ->call('saveProfile')
            ->assertHasNoErrors();
    }

    public function test_uploads_and_replaces_the_photo(): void
    {
        Storage::fake('public');

        Livewire::test(Profile::class)
            ->set('photo', UploadedFile::fake()->image('eu.png', 200, 200))
            ->assertHasNoErrors();

        $first = $this->user->fresh()->avatar_path;
        $this->assertNotNull($first);
        Storage::disk('public')->assertExists($first);
        $this->assertStringContainsString('/storage/'.$first, $this->user->fresh()->avatar_url);

        Livewire::test(Profile::class)->set('photo', UploadedFile::fake()->image('novo.jpg', 200, 200));

        $second = $this->user->fresh()->avatar_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_rejects_files_that_are_not_images_or_are_too_big(): void
    {
        Storage::fake('public');

        Livewire::test(Profile::class)
            ->set('photo', UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf'))
            ->assertHasErrors('photo');

        Livewire::test(Profile::class)
            ->set('photo', UploadedFile::fake()->image('grande.png')->size(3000))
            ->assertHasErrors('photo');

        $this->assertNull($this->user->fresh()->avatar_path);
    }

    public function test_removes_the_photo(): void
    {
        Storage::fake('public');
        $component = Livewire::test(Profile::class)->set('photo', UploadedFile::fake()->image('eu.png'));
        $path = $this->user->fresh()->avatar_path;

        Livewire::test(Profile::class)->call('removePhoto');

        $this->assertNull($this->user->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_changes_the_password_only_with_the_current_one(): void
    {
        $this->user->update(['password' => 'senha-antiga']);

        Livewire::test(Profile::class)
            ->set('currentPassword', 'errada')
            ->set('newPassword', 'senha-nova-123')
            ->set('newPasswordConfirmation', 'senha-nova-123')
            ->call('savePassword')
            ->assertHasErrors('currentPassword');

        Livewire::test(Profile::class)
            ->set('currentPassword', 'senha-antiga')
            ->set('newPassword', 'curta')
            ->set('newPasswordConfirmation', 'outra')
            ->call('savePassword')
            ->assertHasErrors('newPassword');

        Livewire::test(Profile::class)
            ->set('currentPassword', 'senha-antiga')
            ->set('newPassword', 'senha-nova-123')
            ->set('newPasswordConfirmation', 'senha-nova-123')
            ->call('savePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('senha-nova-123', $this->user->fresh()->password));
    }

    public function test_guests_cannot_open_the_profile(): void
    {
        auth()->logout();

        $this->get(route('profile'))->assertRedirect(route('login'));
    }
}
