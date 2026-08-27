<?php

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('failed login event creates login_activity record when user is null', function () {
    event(new Failed('web', null, ['email' => 'nonexistent@example.com', 'password' => 'secret']));

    expect(LoginActivity::count())->toBe(1);

    $activity = LoginActivity::first();
    expect($activity->user_id)->toBeNull();
    expect($activity->status)->toBe('failed');
});

test('failed login event creates login_activity record and increments count when user exists', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'user@example.com',
        'password' => bcrypt('password'),
    ]);

    event(new Failed('web', $user, ['email' => $user->email, 'password' => 'wrong']));

    expect(LoginActivity::count())->toBe(1);

    $activity = LoginActivity::first();
    expect($activity->user_id)->toBe($user->id);
    expect($activity->status)->toBe('failed');

    $user->refresh();
    expect($user->failed_login_count)->toBe(1);
});

test('successful login event creates login_activity record', function () {
    $user = User::create([
        'name' => 'Valid User',
        'email' => 'valid@example.com',
        'password' => bcrypt('password'),
    ]);

    event(new Login('web', $user, false));

    expect(LoginActivity::count())->toBe(1);

    $activity = LoginActivity::first();
    expect($activity->user_id)->toBe($user->id);
    expect($activity->status)->toBe('successful');
});
