<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class TurnstileRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = config('services.turnstile.secret_key');

        // Always pass in testing environment (unless explicitly testing production mode), Cloudflare test keys, or when unconfigured
        if (
            (app()->environment('testing') && config('app.env') !== 'production') ||
            in_array($secretKey, ['1x0000000000000000000000000000000AA', '0x10000000000000000000000000']) ||
            str_starts_with((string) $secretKey, '0x100000') ||
            empty($secretKey)
        ) {
            return;
        }

        if (empty($value)) {
            $fail('Silakan selesaikan verifikasi Cloudflare Turnstile CAPTCHA.');

            return;
        }

        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secretKey,
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
