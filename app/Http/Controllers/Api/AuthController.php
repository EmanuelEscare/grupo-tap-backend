<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ApiTokenService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request, ApiTokenService $tokens): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return ApiResponse::error('Invalid credentials', [], 401);
        }

        return ApiResponse::success('Login successful', [
            'token_type' => 'Bearer',
            'access_token' => $tokens->create($user),
            'user' => UserResource::make($user)->resolve($request),
        ]);
    }

    public function logout(Request $request, ApiTokenService $tokens): JsonResponse
    {
        $token = $request->bearerToken();

        if (is_string($token)) {
            $tokens->delete($token);
        }

        return ApiResponse::success('Logout successful');
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $temporaryPassword = Str::password(16);

        $user = User::query()
            ->where('email', $validated['email'])
            ->firstOrFail();

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
        ])->save();

        Mail::raw(
            "Tu contraseña temporal es: {$temporaryPassword}\n\nCámbiala después de iniciar sesión.",
            function (Message $message) use ($user): void {
                $message
                    ->to($user->email)
                    ->subject('Contraseña temporal');
            }
        );

        return ApiResponse::success('Temporary password sent');
    }
}
