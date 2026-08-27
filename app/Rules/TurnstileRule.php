<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class TurnstileRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Skip validation when running default automated test suite with dummy secret key
        if (config('app.env') === 'testing' && config('services.turnstile.secret_key') === '1x0000000000000000000000000000000AA') {
            return;
        }

        $secretKey = config('services.turnstile.secret_key');

        // Always pass testing secret keys
        if ($secretKey === '1x0000000000000000000000000000000AA' || empty($secretKey)) {
            return;
        }

        if (empty($value)) {
            $fail('Silakan selesaikan verifikasi Cloudflare Turnstile CAPTCHA.');
            return;
        }

        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => $secretKey,
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

            if (! $response->successful() || ! $response->json('success')) {
                $fail('Verifikasi CAPTCHA gagal atau sudah kedaluwarsa. Silakan coba lagi.');
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
