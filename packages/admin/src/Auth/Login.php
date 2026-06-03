<?php

declare(strict_types=1);

namespace Velm\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Velm\Admin\Support\VelmPanel;
use Velm\Framework\Auth\UserProvisioner;

#[Layout('velm-ui::layouts.auth')]
final class Login extends Component
{

    /** @var array{email: string, password: string, remember: bool} */
    public array $data = [
        'email' => '',
        'password' => '',
        'remember' => false,
    ];

    public function getTitle(): string
    {
        return __('Sign in');
    }

    public function authenticate(): mixed
    {
        $key = 'velm-login:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'data.email' => __('Too many login attempts. Please try again in :seconds seconds.', [
                    'seconds' => $seconds,
                ]),
            ]);
        }
        RateLimiter::hit($key, 60);

        $validated = $this->validate([
            'data.email' => ['required', 'string', 'email'],
            'data.password' => ['required', 'string'],
            'data.remember' => ['sometimes', 'boolean'],
        ]);

        $credentials = [
            'email' => (string) ($validated['data']['email'] ?? ''),
            'password' => (string) ($validated['data']['password'] ?? ''),
        ];
        $remember = (bool) ($validated['data']['remember'] ?? false);

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'data.email' => __('These credentials do not match our records.'),
            ]);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        $authenticated = Auth::user();

        if ($authenticated !== null) {
            UserProvisioner::ensureProfile($authenticated);
        }

        return redirect()->intended(VelmPanel::homeUrl());
    }

    public function render()
    {
        return view('velm-ui::auth.login-page');
    }
}
