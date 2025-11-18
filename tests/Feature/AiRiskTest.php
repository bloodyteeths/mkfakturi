<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiRiskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->user->companies()->attach($this->company->id);
    }

    /** @test */
    public function it_returns_risk_score_from_ai_service()
    {
        // Mock AI service response
        Http::fake([
            'http://ai-mcp:3001/api/risk-analysis*' => Http::response([
                'overallRisk' => 0.15,
                'riskLevel' => 'low',
                'factors' => [],
                'recommendations' => [],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/ai/risk?company_id={$this->company->id}");

        $response->assertStatus(200)
            ->assertJson([
                'risk_score' => 0.15,
            ]);
    }

    /** @test */
    public function it_returns_fallback_risk_score_when_ai_service_fails()
    {
        // Mock AI service failure
        Http::fake([
            'http://ai-mcp:3001/api/risk-analysis*' => Http::response(null, 500),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/ai/risk?company_id={$this->company->id}");

        $response->assertStatus(200)
            ->assertJson([
                'risk_score' => 0.5,
            ]);
    }

    /** @test */
    public function it_validates_company_id_parameter()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/ai/risk');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_id']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson("/api/ai/risk?company_id={$this->company->id}");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_caches_risk_score_for_30_minutes()
    {
        Http::fake([
            'http://ai-mcp:3001/api/risk-analysis*' => Http::sequence()
                ->push(['overallRisk' => 0.25], 200)
                ->push(['overallRisk' => 0.35], 200),
        ]);

        // First request
        $response1 = $this->actingAs($this->user)
            ->getJson("/api/ai/risk?company_id={$this->company->id}");

        $response1->assertJson(['risk_score' => 0.25]);

        // Second request should return cached value
        $response2 = $this->actingAs($this->user)
            ->getJson("/api/ai/risk?company_id={$this->company->id}");

        $response2->assertJson(['risk_score' => 0.25]);

        // Only one HTTP request should have been made
        Http::assertSentCount(1);
    }
}
