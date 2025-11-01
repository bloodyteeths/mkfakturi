<?php

use App\Models\User;

it('returns bootstrap payload', function () {
    $this->artisan('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    $user = User::first();
    $this->withHeaders(['company' => $user->companies()->first()->id])
        ->actingAs($user)
        ->getJson('/api/v1/bootstrap')
        ->assertOk();
});
