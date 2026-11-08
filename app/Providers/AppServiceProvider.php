<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Password reset email in the site's own branded layout.
        ResetPassword::toMailUsing(function (User $notifiable, string $token): MailMessage {
            $site = Setting::values();
            $broker = config('auth.defaults.passwords');

            return (new MailMessage)
                ->subject(__('Reset your :site password', ['site' => $site['business_name']]))
                ->markdown('mail.password-reset', [
                    'site' => $site,
                    'name' => $notifiable->name,
                    'url' => route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]),
                    'minutes' => config("auth.passwords.{$broker}.expire"),
                ]);
        });

        // Stops people guessing tracking numbers in bulk.
        RateLimiter::for('tracking', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
