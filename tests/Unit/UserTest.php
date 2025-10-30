<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    // Only seed essential data required for tests
    Artisan::call('db:seed', ['--class' => 'CurrenciesTableSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'CountriesTableSeeder', '--force' => true]);
});

test('user belongs to currency', function () {
    $user = User::factory()->create();

    $this->assertTrue($user->currency()->exists());
});

test('user belongs to many companies', function () {
    $user = User::factory()->hasCompanies(5)->create();

    $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $user->companies);
});
