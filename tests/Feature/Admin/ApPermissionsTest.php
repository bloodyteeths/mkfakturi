<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
});

test('owner can access suppliers and bills endpoints', function () {
    $owner = User::find(1);
    $this->withHeaders([
        'company' => $owner->companies()->first()->id,
    ]);
    Sanctum::actingAs($owner, ['*']);

    getJson('api/v1/suppliers')->assertOk();
    getJson('api/v1/bills')->assertOk();
});

test('non-privileged user without supplier/bill abilities is forbidden', function () {
    $user = User::factory()->create();
    $company = $user->companies()->first() ?: $user->companies()->attach(\App\Models\Company::first()->id);

    $this->withHeaders([
        'company' => \App\Models\Company::first()->id,
    ]);
    Sanctum::actingAs($user, ['*']);

    // These should be forbidden by policies if abilities are not granted
    $responseSuppliers = getJson('api/v1/suppliers');
    $responseBills = getJson('api/v1/bills');

    expect(in_array($responseSuppliers->status(), [401, 403, 404]))->toBeTrue();
    expect(in_array($responseBills->status(), [401, 403, 404]))->toBeTrue();
});
