<?php

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'hr']);
    Role::firstOrCreate(['name' => 'admin']);

    $this->user = User::factory()->create();
    $this->user->assignRole('hr');

    $this->company = Company::factory()->create(['name' => 'Company A']);
    CompanyMember::create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'role' => 'hr',
        'is_active' => true,
    ]);

    $this->otherCompany = Company::factory()->create(['name' => 'Company B']);
    $this->otherUser = User::factory()->create();
    $this->otherUser->assignRole('hr');
    CompanyMember::create([
        'company_id' => $this->otherCompany->id,
        'user_id' => $this->otherUser->id,
        'role' => 'hr',
        'is_active' => true,
    ]);
});

test('HR can access own company dashboard', function () {
    $this->actingAs($this->user)
        ->withSession(['current_company_id' => $this->company->id])
        ->get(route('hr.dashboard'))
        ->assertStatus(200);
});

test('HR cannot access other company via switcher', function () {
    $this->actingAs($this->user)
        ->post(route('hr.companies.switch', $this->otherCompany->id))
        ->assertStatus(404); // firstOrFail in controller
});

test('HR can create internship for own company', function () {
    $this->actingAs($this->user)
        ->withSession(['current_company_id' => $this->company->id])
        ->post(route('hr.internships.store'), [
            'title' => 'Software Engineer Intern',
            'description' => 'Test description',
            'type' => 'WFH',
            'status' => 'published',
        ])
        ->assertRedirect(route('hr.internships.index'));

    expect(Internship::where('company_id', $this->company->id)->exists())->toBeTrue();
});

test('HR can review application in scope', function () {
    $internship = Internship::factory()->create(['company_id' => $this->company->id]);
    $application = Application::factory()->create(['internship_id' => $internship->id]);

    $this->actingAs($this->user)
        ->withSession(['current_company_id' => $this->company->id])
        ->get(route('hr.applications.show', $application->id))
        ->assertStatus(200);
});

test('HR cannot review out-of-scope application', function () {
    $otherInternship = Internship::factory()->create(['company_id' => $this->otherCompany->id]);
    $outOfScopeApplication = Application::factory()->create(['internship_id' => $otherInternship->id]);

    $this->actingAs($this->user)
        ->withSession(['current_company_id' => $this->company->id])
        ->get(route('hr.applications.show', $outOfScopeApplication->id))
        ->assertStatus(403); // Policy
});

test('HR cannot accept/reject out-of-scope application', function () {
    $otherInternship = Internship::factory()->create(['company_id' => $this->otherCompany->id]);
    $outOfScopeApplication = Application::factory()->create(['internship_id' => $otherInternship->id]);

    $this->actingAs($this->user)
        ->withSession(['current_company_id' => $this->company->id])
        ->post(route('hr.applications.update-status', $outOfScopeApplication->id), [
            'status' => 'accepted',
            'notes' => 'Hack attack',
        ])
        ->assertStatus(403);
});

test('Accepting candidate creates audit log', function () {
    $internship = Internship::factory()->create(['company_id' => $this->company->id]);
    $application = Application::factory()->create(['internship_id' => $internship->id, 'status' => 'pending']);

    $this->actingAs($this->user)
        ->withSession(['current_company_id' => $this->company->id])
        ->post(route('hr.applications.update-status', $application->id), [
            'status' => 'accepted',
            'notes' => 'Selamat bergabung!',
        ])
        ->assertStatus(302);

    expect(AuditLog::where('action', 'application_status_updated')
        ->where('auditable_id', $application->id)
        ->exists())->toBeTrue();
});

test('HR can assign mentor from own company', function () {
    $mentorUser = User::factory()->create();
    CompanyMember::create([
        'company_id' => $this->company->id,
        'user_id' => $mentorUser->id,
        'role' => 'mentor',
        'is_active' => true,
    ]);

    $internship = Internship::factory()->create(['company_id' => $this->company->id]);
    $application = Application::factory()->create(['internship_id' => $internship->id]);

    $this->actingAs($this->user)
        ->withSession(['current_company_id' => $this->company->id])
        ->post(route('hr.applications.assign-mentor', $application->id), [
            'mentor_user_id' => $mentorUser->id,
        ])
        ->assertStatus(302);

    expect($application->fresh()->mentor_user_id)->toBe($mentorUser->id);
});

test('HR cannot assign mentor from another company', function () {
    $otherMentorUser = User::factory()->create();
    CompanyMember::create([
        'company_id' => $this->otherCompany->id,
        'user_id' => $otherMentorUser->id,
        'role' => 'mentor',
        'is_active' => true,
    ]);

    $internship = Internship::factory()->create(['company_id' => $this->company->id]);
    $application = Application::factory()->create(['internship_id' => $internship->id]);

    $this->actingAs($this->user)
        ->withSession(['current_company_id' => $this->company->id])
        ->post(route('hr.applications.assign-mentor', $application->id), [
            'mentor_user_id' => $otherMentorUser->id,
        ])
        ->assertSessionHasErrors('mentor_user_id');
});
