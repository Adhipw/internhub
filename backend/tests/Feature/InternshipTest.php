<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternshipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'hr']);
        $this->company = Company::create([
            'user_id' => $this->user->id,
            'name' => 'Test Company',
            'slug' => 'test-company',
        ]);
    }

    public function test_public_can_view_published_internship()
    {
        $internship = Internship::create([
            'company_id' => $this->company->id,
            'title' => 'Published Internship',
            'slug' => 'published-internship',
            'description' => 'Test description',
            'status' => 'published',
            'type' => 'full-time',
        ]);

        $response = $this->get(route('internships.show', $internship->slug));

        $response->assertStatus(200);
        $response->assertSee('Published Internship');
    }

    public function test_public_cannot_view_draft_internship()
    {
        $internship = Internship::create([
            'company_id' => $this->company->id,
            'title' => 'Draft Internship',
            'slug' => 'draft-internship',
            'description' => 'Test description',
            'status' => 'draft',
            'type' => 'full-time',
        ]);

        $response = $this->get(route('internships.show', $internship->slug));

        $response->assertStatus(404);
    }

    public function test_search_filter_works()
    {
        Internship::create([
            'company_id' => $this->company->id,
            'title' => 'Laravel Developer',
            'slug' => 'laravel-developer',
            'description' => 'Building backend with Laravel',
            'status' => 'published',
            'type' => 'full-time',
            'location' => 'Jakarta',
        ]);

        Internship::create([
            'company_id' => $this->company->id,
            'title' => 'Frontend Developer',
            'slug' => 'frontend-developer',
            'description' => 'Building UI with Vue',
            'status' => 'published',
            'type' => 'remote',
            'location' => 'Bandung',
        ]);

        // Search by title
        $response = $this->get(route('internships.index', ['search' => 'Laravel']));
        $response->assertStatus(200);

        // Filter by location
        $response = $this->get(route('internships.index', ['location' => 'Bandung']));
        $response->assertStatus(200);
    }

    public function test_homepage_public_renders_correctly()
    {
        $response = $this->get(route('welcome'));

        $response->assertStatus(200);
        $response->assertSee('InternHub');
    }
}
