<?php

use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->withHeaders([
        'company' => $user->companies()->first()->id,
    ]);
    Sanctum::actingAs(
        $user,
        ['*']
    );
});

test('suppliers search and ordering work with whereCompany scope', function () {
    $company = Company::firstOrFail();

    Supplier::factory()->create([
        'company_id' => $company->id,
        'name' => 'Alpha Supplier',
    ]);
    Supplier::factory()->create([
        'company_id' => $company->id,
        'name' => 'Beta Supplier',
    ]);

    $response = getJson('api/v1/suppliers?search=Alpha&orderByField=name&orderBy=asc');

    $response->assertOk();
    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names)->toContain('Alpha Supplier');
});

test('suppliers multi-tenant isolation prevents cross-company leakage', function () {
    $companyA = Company::firstOrFail();
    $companyB = Company::factory()->create();

    Supplier::factory()->create([
        'company_id' => $companyA->id,
        'name' => 'Company A Supplier',
    ]);

    $this->withHeaders([
        'company' => $companyB->id,
    ]);
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $response = getJson('api/v1/suppliers');
    $response->assertOk();

    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names)->not()->toContain('Company A Supplier');
});

