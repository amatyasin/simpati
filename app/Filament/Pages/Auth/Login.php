<?php

namespace App\Filament\Pages\Auth;

use App\Rules\TurnstileRule;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return parent::form($schema)
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                ViewField::make('turnstile')
                    ->view('components.turnstile')
                    ->dehydrated(false),
            ]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $turnstileResponse = request()->input('cf-turnstile-response') ?? request()->input('turnstile') ?? null;

        $validator = validator(
            ['turnstile' => $turnstileResponse],
            ['turnstile' => [new TurnstileRule()]]
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'data.email' => $validator->errors()->first('turnstile'),
            ]);
        }

        return parent::getCredentialsFromFormData($data);
    }
}
