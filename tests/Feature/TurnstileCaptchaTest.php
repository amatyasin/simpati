<?php

use App\Models\User;
use App\Rules\TurnstileRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

test('turnstile rule passes during testing environment', function () {
    $validator = Validator::make(
        ['turnstile' => null],
        ['turnstile' => [new TurnstileRule()]]
    );

    expect($validator->passes())->toBeTrue();
});

test('turnstile rule validates token against cloudflare api in production mode', function () {
    config(['app.env' => 'production']);
    config(['services.turnstile.secret_key' => 'live_secret_key_12345']);

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
    ]);

    $validator = Validator::make(
        ['turnstile' => 'valid-token-xyz'],
        ['turnstile' => [new TurnstileRule()]]
    );

    expect($validator->passes())->toBeTrue();
});

test('turnstile rule fails when cloudflare returns failure', function () {
    config(['app.env' => 'production']);
    config(['services.turnstile.secret_key' => 'live_secret_key_12345']);

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => false], 200),
    ]);

    $validator = Validator::make(
        ['turnstile' => 'invalid-token-xyz'],
        ['turnstile' => [new TurnstileRule()]]
    );

    expect($validator->fails())->toBeTrue();
});
