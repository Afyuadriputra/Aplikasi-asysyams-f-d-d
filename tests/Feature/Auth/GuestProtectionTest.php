<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_attendance(): void
    {
        $this->get('/attendance')->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_transcript(): void
    {
        $this->get('/transcript')->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect();
    }
}