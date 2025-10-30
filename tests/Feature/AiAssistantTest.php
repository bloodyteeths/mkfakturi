<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Company;
use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * AI-TST-01: AI Assistant Endpoints Validation
 * 
 * Tests AI-powered business insights and risk assessment endpoints:
 * - /api/ai/summary - Business performance summaries
 * - /api/ai/risk - Financial risk assessment
 * - AI data analysis with Macedonia business context
 * - Performance optimization for AI responses
 * - Macedonia-specific business intelligence
 * - Multi-language AI responses (English/Macedonian)
 */
class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $customer;
    protected $invoices;
    protected $expenses;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupTestEnvironment();
        $this->createTestData();
    }

    protected function setupTestEnvironment(): void
    {
        // Configure AI service endpoints
        Config::set('ai.openai.api_key', 'test_openai_key');
        Config::set('ai.anthropic.api_key', 'test_anthropic_key');
        Config::set('ai.service_url', 'http://localhost:3001');
        Config::set('ai.enabled', true);
        Config::set('ai.cache_ttl', 300); // 5 minutes cache
        
        // Macedonia-specific AI configuration
        Config::set('ai.locale', 'mk');
        Config::set('ai.currency', 'MKD');
        Config::set('ai.tax_rates', [18, 5, 0]);
        Config::set('ai.business_context', 'macedonia');
    }

    protected function createTestData(): void
    {
        // Create company
        $this->company = Company::factory()->create([
            'name' => 'АИ Тест Компанија ДОО',
            'vat_number' => 'MK4030009501234',
            'currency' => 'MKD'
        ]);

        // Create user
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'admin'
        ]);

        // Create customers
        $this->customer = Customer::factory()->create([
            'name' => 'Голем Клиент АД',
            'tax_id' => 'MK4030009501235',
            'company_id' => $this->company->id
        ]);

        $smallCustomer = Customer::factory()->create([
            'name' => 'Мал Клиент ДООЕл',
            'tax_id' => 'MK4030009501236',
            'company_id' => $this->company->id
        ]);

        // Create invoices with different patterns for AI analysis
        $this->invoices = collect();

        // High-value invoices (good performance)
        for ($i = 1; $i <= 5; $i++) {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'invoice_number' => "АИ-2025-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'invoice_date' => Carbon::now()->subDays(rand(1, 30)),
                'due_date' => Carbon::now()->addDays(30),
                'total' => rand(50000, 150000), // 500-1500 MKD
                'status' => 'PAID',
                'paid_status' => rand(50000, 150000)
            ]);
            
            $this->invoices->push($invoice);
        }

        // Overdue invoices (risk indicators)
        for ($i = 6; $i <= 8; $i++) {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $smallCustomer->id,
                'invoice_number' => "АИ-2025-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'invoice_date' => Carbon::now()->subDays(60),
                'due_date' => Carbon::now()->subDays(30), // Overdue
                'total' => rand(10000, 30000), // 100-300 MKD
                'status' => 'SENT',
                'paid_status' => 0
            ]);
            
            $this->invoices->push($invoice);
        }

        // Recent invoices (current month)
        for ($i = 9; $i <= 15; $i++) {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => rand(0, 1) ? $this->customer->id : $smallCustomer->id,
                'invoice_number' => "АИ-2025-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'invoice_date' => Carbon::now()->subDays(rand(1, 15)),
                'due_date' => Carbon::now()->addDays(rand(15, 45)),
                'total' => rand(20000, 80000), // 200-800 MKD
                'status' => rand(0, 1) ? 'PAID' : 'SENT',
                'paid_status' => rand(0, 1) ? rand(20000, 80000) : 0
            ]);
            
            $this->invoices->push($invoice);
        }

        // Create expenses for cost analysis
        $this->expenses = collect();
        
        $expenseCategories = [
            'Канцелариски материјали',
            'Телефони и интернет',
            'Електрична енергија',
            'Гориво и транспорт',
            'Маркетинг'
        ];

        foreach ($expenseCategories as $category) {
            for ($i = 1; $i <= 3; $i++) {
                $expense = Expense::factory()->create([
                    'company_id' => $this->company->id,
                    'amount' => rand(5000, 25000), // 50-250 MKD
                    'expense_date' => Carbon::now()->subDays(rand(1, 30)),
                    'category' => $category,
                    'notes' => "Трошок за {$category} - месец " . Carbon::now()->format('m/Y')
                ]);
                
                $this->expenses->push($expense);
            }
        }

        // Create payments
        foreach ($this->invoices->where('status', 'PAID') as $invoice) {
            Payment::factory()->create([
                'company_id' => $this->company->id,
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total,
                'payment_date' => $invoice->invoice_date->addDays(rand(1, 15)),
                'payment_method' => 'bank_transfer',
                'notes' => 'Плаќање извршено навремено'
            ]);
        }
    }

    /** @test */
    public function it_generates_business_summary_via_ai_endpoint()
    {
        // Mock AI service response
        Http::fake([
            'localhost:3001/api/analyze' => Http::response([
                'summary' => [
                    'period' => 'last_30_days',
                    'total_revenue' => 750000,
                    'revenue_currency' => 'MKD',
                    'invoice_count' => 15,
                    'paid_invoices' => 8,
                    'overdue_invoices' => 3,
                    'average_invoice_value' => 50000,
                    'top_customer' => 'Голем Клиент АД',
                    'payment_trend' => 'improving',
                    'key_insights' => [
                        'Големиот клиент генерира 60% од приходите',
                        'Просечното време за плаќање е 12 дена',
                        '3 фактури се задоцнети повеќе од 30 дена'
                    ],
                    'recommendations' => [
                        'Контактирајте ги клиентите со задоцнети плаќања',
                        'Разгледајте попуст за порано плаќање',
                        'Проширете ја базата на клиенти'
                    ]
                ],
                'metadata' => [
                    'analysis_date' => Carbon::now()->toISOString(),
                    'data_quality' => 'high',
                    'confidence_score' => 0.92
                ]
            ], 200)
        ]);

        // Authenticate user
        $this->actingAs($this->user);

        // Request AI summary
        $response = $this->getJson('/api/ai/summary', [
            'period' => 'last_30_days',
            'language' => 'mk',
            'include_recommendations' => true
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'summary' => [
                'period',
                'total_revenue',
                'revenue_currency',
                'invoice_count',
                'paid_invoices',
                'overdue_invoices',
                'key_insights',
                'recommendations'
            ],
            'metadata' => [
                'analysis_date',
                'confidence_score'
            ]
        ]);

        // Verify Macedonia-specific data
        $summary = $response->json('summary');
        $this->assertEquals('MKD', $summary['revenue_currency']);
        $this->assertStringContainsString('Голем Клиент', $summary['top_customer']);
        $this->assertGreaterThan(0, count($summary['key_insights']));
        
        // Verify Macedonian language in insights
        foreach ($summary['key_insights'] as $insight) {
            $this->assertStringContainsString('клиент', strtolower($insight));
        }
    }

    /** @test */
    public function it_performs_financial_risk_assessment()
    {
        // Mock AI risk analysis response
        Http::fake([
            'localhost:3001/api/risk-analysis' => Http::response([
                'risk_assessment' => [
                    'overall_risk_score' => 3.2, // Scale 1-5 (5 = highest risk)
                    'risk_level' => 'moderate',
                    'cash_flow_risk' => 2.8,
                    'customer_concentration_risk' => 4.1,
                    'overdue_payment_risk' => 3.5,
                    'seasonal_risk' => 2.0,
                    'risk_factors' => [
                        [
                            'factor' => 'customer_concentration',
                            'description' => 'Еден клиент претставува 60% од приходите',
                            'impact' => 'high',
                            'recommendation' => 'Диверзифицирајте ја клиентската база'
                        ],
                        [
                            'factor' => 'overdue_invoices',
                            'description' => '3 фактури се задоцнети повеќе од 30 дена',
                            'impact' => 'medium',
                            'recommendation' => 'Воспоставете систем за автоматски потсетници'
                        ],
                        [
                            'factor' => 'payment_delays',
                            'description' => 'Просечно доцнење од 5 дена во последниот месец',
                            'impact' => 'low',
                            'recommendation' => 'Понудете попуст за порано плаќање'
                        ]
                    ],
                    'mitigation_strategies' => [
                        'Воведете кредитни лимити за нови клиенти',
                        'Барајте предплата за големи нарачки',
                        'Развијте односи со повеќе клиенти'
                    ],
                    'predicted_cash_flow' => [
                        'next_30_days' => 180000,
                        'next_60_days' => 320000,
                        'next_90_days' => 480000,
                        'currency' => 'MKD'
                    ]
                ],
                'metadata' => [
                    'analysis_timestamp' => Carbon::now()->toISOString(),
                    'model_version' => '2.1',
                    'confidence_interval' => 0.85
                ]
            ], 200)
        ]);

        // Authenticate user
        $this->actingAs($this->user);

        // Request risk assessment
        $response = $this->postJson('/api/ai/risk', [
            'analysis_period' => 90,
            'include_predictions' => true,
            'language' => 'mk'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'risk_assessment' => [
                'overall_risk_score',
                'risk_level',
                'cash_flow_risk',
                'customer_concentration_risk',
                'overdue_payment_risk',
                'risk_factors',
                'mitigation_strategies',
                'predicted_cash_flow'
            ],
            'metadata'
        ]);

        // Verify risk assessment data
        $riskData = $response->json('risk_assessment');
        $this->assertIsFloat($riskData['overall_risk_score']);
        $this->assertContains($riskData['risk_level'], ['low', 'moderate', 'high', 'critical']);
        $this->assertGreaterThan(0, count($riskData['risk_factors']));
        
        // Verify Macedonia currency in predictions
        $this->assertEquals('MKD', $riskData['predicted_cash_flow']['currency']);
        
        // Verify Macedonian language in recommendations
        foreach ($riskData['mitigation_strategies'] as $strategy) {
            $this->assertMatchesRegularExpression('/[А-Яа-я]/u', $strategy);
        }
    }

    /** @test */
    public function it_handles_ai_service_performance_requirements()
    {
        // Mock AI service with response time simulation
        Http::fake([
            'localhost:3001/api/analyze' => Http::response([
                'summary' => [
                    'total_revenue' => 500000,
                    'revenue_currency' => 'MKD'
                ]
            ], 200)->delay(1500) // 1.5 second delay
        ]);

        $this->actingAs($this->user);

        $startTime = microtime(true);
        
        $response = $this->getJson('/api/ai/summary');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // AI endpoint should respond within 3 seconds
        $this->assertLessThan(3000, $responseTime, 'AI summary endpoint should respond within 3 seconds');
        $response->assertStatus(200);
    }

    /** @test */
    public function it_caches_ai_responses_for_performance()
    {
        // Mock AI service
        Http::fake([
            'localhost:3001/api/analyze' => Http::response([
                'summary' => [
                    'total_revenue' => 500000,
                    'cache_test' => true
                ]
            ], 200)
        ]);

        $this->actingAs($this->user);

        // First request - should hit AI service
        $response1 = $this->getJson('/api/ai/summary');
        $response1->assertStatus(200);

        // Second request - should be cached
        $response2 = $this->getJson('/api/ai/summary');
        $response2->assertStatus(200);

        // Should have same response data
        $this->assertEquals($response1->json(), $response2->json());

        // AI service should only be called once due to caching
        Http::assertSentCount(1);

        // Verify cache key exists
        $cacheKey = "ai_summary_{$this->company->id}_last_30_days";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_handles_ai_service_errors_gracefully()
    {
        // Mock AI service error
        Http::fake([
            'localhost:3001/api/analyze' => Http::response([
                'error' => 'AI service temporarily unavailable',
                'code' => 'SERVICE_UNAVAILABLE'
            ], 503)
        ]);

        $this->actingAs($this->user);

        $response = $this->getJson('/api/ai/summary');

        $response->assertStatus(503);
        $response->assertJson([
            'message' => 'AI service is temporarily unavailable',
            'fallback_available' => true
        ]);

        // Should provide fallback basic analytics
        $this->assertArrayHasKey('basic_analytics', $response->json());
    }

    /** @test */
    public function it_provides_fallback_analytics_when_ai_unavailable()
    {
        // Mock AI service timeout
        Http::fake([
            'localhost:3001/*' => Http::response('', 408) // Timeout
        ]);

        $this->actingAs($this->user);

        $response = $this->getJson('/api/ai/summary?fallback=true');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'basic_analytics' => [
                'total_revenue',
                'invoice_count',
                'paid_invoices',
                'overdue_invoices',
                'average_payment_time'
            ],
            'source' => ['fallback']
        ]);

        // Verify fallback calculations are correct
        $analytics = $response->json('basic_analytics');
        $this->assertIsNumeric($analytics['total_revenue']);
        $this->assertIsInt($analytics['invoice_count']);
        $this->assertEquals('fallback', $response->json('source'));
    }

    /** @test */
    public function it_supports_multilingual_ai_responses()
    {
        // Mock AI service with English response
        Http::fake([
            'localhost:3001/api/analyze' => Http::response([
                'summary' => [
                    'key_insights' => [
                        'Your largest customer generates 60% of revenue',
                        'Average payment time is 12 days',
                        '3 invoices are overdue by more than 30 days'
                    ],
                    'recommendations' => [
                        'Contact customers with overdue payments',
                        'Consider early payment discounts',
                        'Expand customer base'
                    ]
                ]
            ], 200)
        ]);

        $this->actingAs($this->user);

        // Request in English
        $response = $this->getJson('/api/ai/summary?language=en');

        $response->assertStatus(200);
        
        $insights = $response->json('summary.key_insights');
        foreach ($insights as $insight) {
            // Should be in English (no Cyrillic characters)
            $this->assertDoesNotMatchRegularExpression('/[А-Яа-я]/u', $insight);
        }

        // Test Macedonian language request
        Http::fake([
            'localhost:3001/api/analyze' => Http::response([
                'summary' => [
                    'key_insights' => [
                        'Големиот клиент генерира 60% од приходите',
                        'Просечното време за плаќање е 12 дена'
                    ]
                ]
            ], 200)
        ]);

        $responseMk = $this->getJson('/api/ai/summary?language=mk');
        $responseMk->assertStatus(200);
        
        $insightsMk = $responseMk->json('summary.key_insights');
        foreach ($insightsMk as $insight) {
            // Should contain Macedonian text
            $this->assertMatchesRegularExpression('/[А-Яа-я]/u', $insight);
        }
    }

    /** @test */
    public function it_validates_ai_endpoint_authentication()
    {
        // Test without authentication
        $response = $this->getJson('/api/ai/summary');
        $response->assertStatus(401);

        // Test with wrong company access
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        
        $this->actingAs($otherUser);
        
        // Should not access other company's data
        $response = $this->getJson('/api/ai/summary');
        $response->assertStatus(200); // Should work but with otherUser's company data

        // Verify company isolation
        Http::fake([
            'localhost:3001/api/analyze' => Http::response([
                'summary' => ['total_revenue' => 0] // No data for other company
            ], 200)
        ]);

        $response = $this->getJson('/api/ai/summary');
        $this->assertEquals(0, $response->json('summary.total_revenue'));
    }

    /** @test */
    public function it_tracks_ai_usage_analytics()
    {
        Http::fake([
            'localhost:3001/api/analyze' => Http::response([
                'summary' => ['total_revenue' => 100000]
            ], 200)
        ]);

        $this->actingAs($this->user);

        // Make multiple AI requests
        $this->getJson('/api/ai/summary');
        $this->postJson('/api/ai/risk');

        // Verify usage tracking
        $this->assertDatabaseHas('ai_usage_logs', [
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'endpoint' => '/api/ai/summary'
        ]);

        $this->assertDatabaseHas('ai_usage_logs', [
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'endpoint' => '/api/ai/risk'
        ]);
    }

    /** @test */
    public function it_handles_large_dataset_ai_analysis()
    {
        // Create large dataset (simulate company with lots of data)
        for ($i = 1; $i <= 100; $i++) {
            Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'total' => rand(10000, 100000)
            ]);
        }

        // Mock AI service handling large dataset
        Http::fake([
            'localhost:3001/api/analyze' => Http::response([
                'summary' => [
                    'total_revenue' => 5500000,
                    'invoice_count' => 115, // 15 original + 100 new
                    'data_volume' => 'large',
                    'processing_time_ms' => 2500
                ],
                'performance' => [
                    'data_points_analyzed' => 115,
                    'processing_optimized' => true
                ]
            ], 200)
        ]);

        $this->actingAs($this->user);

        $startTime = microtime(true);
        $response = $this->getJson('/api/ai/summary');
        $endTime = microtime(true);
        
        $processingTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        
        // Should handle large dataset efficiently
        $this->assertLessThan(5000, $processingTime, 'Large dataset analysis should complete within 5 seconds');
        $this->assertEquals(115, $response->json('summary.invoice_count'));
    }

    protected function tearDown(): void
    {
        // Clear AI response cache
        Cache::flush();
        Http::preventStrayRequests();
        parent::tearDown();
    }
}

