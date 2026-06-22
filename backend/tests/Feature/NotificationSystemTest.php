<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use App\Notifications\InternshipApplied;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');
    }

    #[Test]
    public function notification_can_be_dispatched_and_stored()
    {
        Notification::fake();

        $application = Application::factory()->create();
        $this->user->notify(new InternshipApplied($application));

        Notification::assertSentTo($this->user, InternshipApplied::class);
    }

    #[Test]
    public function users_can_only_access_their_own_notification_channel()
    {
        $otherUser = User::factory()->create();

        // Testing the authorization callback from channels.php
        $this->actingAs($this->user);

        $this->assertTrue(
            Gate::forUser($this->user)->allows('view-notification-channel', $this->user->id)
        );

        $this->assertFalse(
            Gate::forUser($this->user)->allows('view-notification-channel', $otherUser->id)
        );
    }

    #[Test]
    public function non_super_admin_cannot_access_horizon()
    {
        $response = $this->actingAs($this->admin)->get('/horizon');
        $response->assertStatus(403);
    }

    #[Test]
    public function super_admin_can_access_horizon()
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->get('/horizon');
        // If horizon is installed but not running, it might 404 or redirect,
        // but here we check for the authorization gate.
        // Usually, it's redirected to login or 403.
        $this->assertNotEquals(403, $response->getStatusCode());
    }
}
