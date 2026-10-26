<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Testes com banco rodam logados como $this->user; as classes de login/isolamento desligam isso. */
    protected bool $signIn = true;

    protected ?User $user = null;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->signIn && in_array(RefreshDatabase::class, class_uses_recursive($this), true)) {
            $this->user = User::factory()->create();
            $this->actingAs($this->user);
        }
    }
}
