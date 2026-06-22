<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Internship;
use App\Models\User;
use App\Services\AI\AiManager;
use App\Services\AI\AiService;
use App\Services\AI\DTOs\AiResponse;
use App\Services\AI\Logging\AiUsageLogger;
use App\Services\AI\Safety\SafetyGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AiHrMentorTest extends TestCase
{
    use RefreshDatabase;

    protected $hr;

    protected $mentor;

    protected $company;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'mentor', 'guard_name' => 'web']);

        $this->company = Company::factory()->create();
        $this->hr = User::factory()->create(['role' => 'hr', 'is_active' => true]);
        $this->hr->assignRole('hr');

        CompanyMember::create([
            'company_id' => $this->company->id,
            'user_id' => $this->hr->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->mentor = User::factory()->create(['role' => 'mentor', 'is_active' => true]);
        $this->mentor->assignRole('mentor');

        CompanyMember::create([
            'company_id' => $this->company->id,
            'user_id' => $this->mentor->id,
            'role' => 'mentor',
            'is_active' => true,
        ]);

        session(['current_company_id' => $this->company->id]);
    }

    #[Test]
    public function hr_ai_is_scoped_to_company()
    {
        $otherCompany = Company::factory()->create();
        $internship = Internship::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($this->hr);

        $response = $this->getJson(route('hr.ai.pipeline-insight', [
            'internship_id' => $internship->id,
        ]));

        $response->assertStatus(403);
    }

    #[Test]
    public function mentor_ai_is_scoped_to_assigned_mentee()
    {
        $otherMentor = User::factory()->create(['role' => 'mentor', 'is_active' => true]);
        $application = Application::factory()->create([
            'mentor_user_id' => $otherMentor->id,
        ]);

        $this->actingAs($this->mentor);

        $response = $this->postJson(route('mentor.ai.generate-tasks'), [
            'application_id' => $application->id,
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function fairness_guard_blocks_discriminatory_output()
    {
        $this->actingAs($this->hr);

        // Mock AI to return discriminatory text
        $mockManager = \Mockery::mock(AiManager::class);
        $mockManager->shouldReceive('generate')->andReturn(
            new AiResponse('The candidate is not a good fit because of age.', ['provider' => 'mock'])
        );

        $service = new AiService($mockManager, app(SafetyGuard::class), app(AiUsageLogger::class));

        // We override the singleton for this test
        $this->app->instance(AiService::class, $service);

        $response = $this->postJson(route('hr.ai.generate-job-desc'), [
            'title' => 'Software Engineer',
        ]);

        $response->assertStatus(500); // Exception thrown and caught as 500
        $this->assertStringContainsString('discriminatory factors', $response->json('message'));
    }
}
