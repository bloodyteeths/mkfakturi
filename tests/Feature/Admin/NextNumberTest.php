<?php

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

test('next number', function () {
    $key = 'invoice';

    $response = getJson('api/v1/next-number?key='.$key);

    $response->assertStatus(200)->assertJson([
        'nextNumber' => 'INV-000001',
    ]);

    $key = 'estimate';

    $response = getJson('api/v1/next-number?key='.$key);

    $response->assertStatus(200)->assertJson([
        'nextNumber' => 'EST-000001',
    ]);

    $key = 'payment';

    $response = getJson('api/v1/next-number?key='.$key);

    $response->assertStatus(200)->assertJson([
        'nextNumber' => 'PAY-000001',
    ]);
});

test('next number falls back to default format when missing setting', function () {
    $user = User::find(1);
    $companyId = $user->companies()->first()->id;

    \App\Models\CompanySetting::where('company_id', $companyId)
        ->where('option', 'invoice_number_format')
        ->delete();

    $response = getJson('api/v1/next-number?key=invoice');

    $response->assertStatus(200);

    $expectedPrefix = 'INV-'.date('Y').'-';

    expect($response->json('nextNumber'))
        ->toStartWith($expectedPrefix)
        ->and($response->json('nextNumber'))
        ->toEndWith('000001');
});
