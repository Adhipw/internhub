<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDetail;
use App\Services\AI\AiManager;
use App\Services\AI\DTOs\AiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AiBatch14Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ai.default' => 'fake']);
        // Clear specific rate limits
        RateLimiter::clear('ai-usage:127.0.0.1');
        RateLimiter::clear('ai-usage:public-finder:127.0.0.1');
        RateLimiter::clear('ai-usage:public-faq:127.0.0.1');

        // Global mock for AiManager to prevent external API calls & cURL/SSL certificate errors
        $this->mock(AiManager::class, function ($mock) {
            $mock->shouldReceive('generate')->andReturn(new AiResponse(
                '{"matches": [], "tips": "Mocked AI response"}', []
            ));
        });
    }

    public function test_public_ai_finder_is_rate_limited()
    {
        $ip = '127.0.0.1';

        // 20 requests should succeed (based on AiPublicController config)
        for ($i = 0; $i < 20; $i++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->postJson(route('ai.public.finder'), ['prompt' => 'Search test']);
            $response->assertStatus(200);
        }

        // 21st request should fail
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson(route('ai.public.finder'), ['prompt' => 'Search test']);

        $response->assertStatus(429);
    }

    public function test_cv_summary_requires_consent()
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);

        // Without consent
        $response = $this->actingAs($user)
            ->postJson(route('ai.summarize-cv'), [
                'cv_text' => 'My skills are...',
            ]);
        $response->assertStatus(403);

        // With consent
        UserDetail::factory()->create([
            'user_id' => $user->id,
            'ai_consent' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('ai.summarize-cv'), [
                'cv_text' => 'My skills are...',
            ]);
        $response->assertStatus(200);
    }

    public function test_user_cannot_access_ai_without_verification()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($user)
            ->postJson(route('ai.review-profile'));

        $response->assertStatus(403);
    }

    public function test_ai_usage_is_logged()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);

        UserDetail::factory()->create([
            'user_id' => $user->id,
            'ai_consent' => true,
        ]);

        $this->actingAs($user)
            ->getJson(route('ai.recommendations'));

        $this->assertDatabaseHas('ai_usage_logs', [
            'user_id' => $user->id,
        ]);
    }
}
