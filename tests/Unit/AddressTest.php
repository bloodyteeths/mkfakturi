<?php

use App\Models\Address;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    // Only seed essential data required for tests
    Artisan::call('db:seed', ['--class' => 'CurrenciesTableSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'CountriesTableSeeder', '--force' => true]);
});

test('an address belongs to user', function () {
    $address = Address::factory()->forUser()->create();

    $this->assertTrue($address->user->exists());
});

test('an address belongs to country', function () {
    $address = Address::factory()->create();

    $this->assertTrue($address->country->exists());
});

test('an address belongs to customer', function () {
    $address = Address::factory()->forCustomer()->create();

    $this->assertTrue($address->customer()->exists());
});
