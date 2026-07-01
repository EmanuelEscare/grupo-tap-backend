<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

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
        Auth::viaRequest('api-token', function (Request $request): ?User {
            $token = $request->bearerToken();

            if (! is_string($token) || $token === '') {
                return null;
            }

            $record = DB::table('api_tokens')
                ->where('token_hash', app(ApiTokenService::class)->hash($token))
                ->first();

            $userId = data_get($record, 'user_id');

            if (! is_string($userId) || $userId === '') {
                return null;
            }

            return User::query()->find($userId);
        });
    }
}
