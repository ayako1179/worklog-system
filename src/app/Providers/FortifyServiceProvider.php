<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Features;
use App\Providers\RouteServiceProvider;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        config(['fortify.features' => [
            Features::registration(),
            Features::emailVerification(),
        ]]);

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::loginView(function () {
            return view('auth.login');
        });
        Fortify::registerView(function () {
            return view('auth.register');
        });
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });
        Fortify::redirects('login', function () {
            return '/attendance';
        });
        Fortify::redirects('verification', function () {
            return '/attendance';
        });

        $this->app->bind(
            \Laravel\Fortify\Http\Requests\LoginRequest::class,
            \App\Http\Requests\Auth\LoginRequest::class
        );

        $this->app->singleton(LoginResponse::class, function () {
            return new class implements LoginResponse {
                public function toResponse($request)
                {
                    $user = $request->user();

                    if ($user->role === 'admin') {
                        return redirect()->route('admin.attendance.list');
                    }

                    return redirect()->route('home');
                }
            };
        });

        $this->app->singleton(RegisterResponse::class, function () {
            return new class implements RegisterResponse {
                public function toResponse($request)
                {
                    $user = $request->user();

                    if (!$user->hasVerifiedEmail()) {
                        return redirect()->route('verification.notice');
                    }

                    return redirect()->intended(RouteServiceProvider::HOME);
                }
            };
        });

        $this->app->singleton(LogoutResponse::class, function () {
            return new class implements LogoutResponse {
                public function toResponse($request)
                {
                    $role = $request->cookie('logout_role');

                    \Cookie::queue(\Cookie::forget('logout_role'));

                    if ($role === 'admin') {
                        return redirect()->route('admin.login');
                    }

                    return redirect()->route('login');
                }
            };
        });

        Fortify::authenticateUsing(function ($request) {
            $user = \App\Models\User::where('email', $request->email)->first();
            if (! $user) {
                throw ValidationException::withMessages([
                    'email' => ['ログイン情報が登録されていません'],
                ]);
            }

            $loginType = $request->input('login_type', 'staff');
            if ($loginType === 'admin' && $user->role !== 'admin') {
                throw ValidationException::withMessages([
                    'email' => ['ログイン情報が登録されていません'],
                ]);
            }

            if ($loginType === 'staff' && $user->role !== 'staff') {
                throw ValidationException::withMessages([
                    'email' => ['ログイン情報が登録されていません'],
                ]);
            }

            if (! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['ログイン情報が登録されていません'],
                ]);
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(10)->by($email . $request->ip());
        });
    }
}
