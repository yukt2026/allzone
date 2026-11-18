<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password');
        $remember = $this->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($this->throttleKey());

            if (function_exists('activity')) {
                activity('security')
                    ->event('failed_login')
                    ->withProperties([
                        'ip' => $this->ip(),
                        'user_agent' => $this->userAgent(),
                        'attempted_email' => (string) $this->input('email'),
                    ])
                    ->log("Failed administrator login attempt for '{$this->input('email')}'");
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Verify that authenticated user actually has admin access
        $user = Auth::user();
        $allowedRoles = [
            'super_admin',
            'admin',
            'operations_admin',
            'support_admin',
            'seller_support',
            'catalog_manager',
            'finance_admin',
            'finance_manager',
            'support_staff',
        ];

        if (!$user || !$user->hasAnyRole($allowedRoles)) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            if (function_exists('activity')) {
                activity('security')
                    ->event('unauthorized_admin_access')
                    ->withProperties([
                        'ip' => $this->ip(),
                        'user_agent' => $this->userAgent(),
                        'attempted_email' => (string) $this->input('email'),
                    ])
                    ->log("Unauthorized non-admin role attempted administrative access: '{$this->input('email')}'");
            }

            throw ValidationException::withMessages([
                'email' => 'Access denied. You do not possess administrator credentials.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        if (function_exists('activity')) {
            activity('security')
                ->event('account_lockout')
                ->withProperties([
                    'ip' => $this->ip(),
                    'attempted_email' => (string) $this->input('email'),
                ])
                ->log("Administrator account rate limit lockout triggered for '{$this->input('email')}'");
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate('admin|' . Str::lower($this->input('email')) . '|' . $this->ip());
    }
}
