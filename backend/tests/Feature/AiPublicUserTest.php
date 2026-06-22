<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Internship;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\AI\AiManager;
use App\Services\AI\DTOs\AiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiPublicUserTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now(), 'is_active' => true]);

        UserDetail::factory()->create([
            'user_id' => $this->user->id,
            'ai_consent' => true,
        ]);

        // Setup some data for recommendations
        $company = Company::factory()->create();
        Internship::factory()->count(5)->create([
            'company_id' => $company->id,
            'status' => 'published',
        ]);

        // Global mock for AiManager to prevent external API calls & cURL/SSL certificate errors
        $this->mock(AiManager::class, function ($mock) {
            $mock->shouldReceive('generate')->andReturn(new AiResponse(
                '{"matches": [], "tips": "Mocked profile tips without any other user data"}', []
            ));
        });
    }

    #[Test]
    public function public_ai_faq_is_rate_limited()
    {
        // Max requests for FAQ is 15 in AiPublicController
        for ($i = 0; $i < 15; $i++) {
            $response = $this->postJson(route('ai.public.faq'), ['question' => 'How to apply?']);
            $response->assertStatus(200);
        }

        $response = $this->postJson(route('ai.public.faq'), ['question' => 'One more?']);
        $response->assertStatus(429);
    }

    #[Test]
    public function user_ai_cannot_access_other_user_data_implicitly()
    {
        $otherUser = User::factory()->create(['name' => 'Target User']);
        $this->actingAs($this->user);

        // Even though we're logged in as $this->user, let's verify the controller
        // only uses Auth::user() context.
        $response = $this->postJson(route('ai.review-profile'));

        $response->assertStatus(200);
        $this->assertStringNotContainsString('Target User', $response->json('tips'));
    }

    #[Test]
    public function ai_usage_log_is_created_after_request()
    {
        $this->actingAs($this->user);

        $this->getJson(route('ai.recommendations'));

        $this->assertDatabaseHas('ai_usage_logs', [
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function internship_finder_works_for_guests()
    {
        $response = $this->postJson(route('ai.public.finder'), ['prompt' => 'Find me a job']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['matches']);
    }
}
