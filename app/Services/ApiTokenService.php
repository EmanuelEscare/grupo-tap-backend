<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiTokenService
{
    public function create(User $user): string
    {
        $plainTextToken = Str::random(80);

        DB::table('api_tokens')->insert([
            'user_id' => (string) $user->getKey(),
            'token_hash' => $this->hash($plainTextToken),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $plainTextToken;
    }

    public function delete(string $plainTextToken): void
    {
        DB::table('api_tokens')
            ->where('token_hash', $this->hash($plainTextToken))
            ->delete();
    }

    public function hash(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }
}
