<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AutomaticCodeGenerator
{
    public function userCode(): string
    {
        return $this->generate(User::class, 'USR');
    }

    public function productCode(): string
    {
        return $this->generate(Product::class, 'PROD');
    }

    public function profileCode(): string
    {
        return $this->generate(Profile::class, 'PER');
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function generate(string $modelClass, string $prefix): string
    {
        $latestCode = $modelClass::query()
            ->where('code', 'like', "{$prefix}-%")
            ->orderByDesc('code')
            ->value('code');

        $nextNumber = 1;

        if (is_string($latestCode)) {
            $suffix = substr($latestCode, strlen($prefix) + 1);

            if (ctype_digit($suffix)) {
                $nextNumber = (int) $suffix + 1;
            }
        }

        return sprintf('%s-%06d', $prefix, $nextNumber);
    }
}
