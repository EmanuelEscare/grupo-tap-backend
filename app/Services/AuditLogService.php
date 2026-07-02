<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Profile;
use App\Models\User;

class AuditLogService
{
    /**
     * @return array<string, mixed>
     */
    public function productSnapshot(Product $product): array
    {
        return [
            'code' => $product->code,
            'name' => $product->name,
            'brand' => $product->brand,
            'price' => $product->price,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function userSnapshot(User $user): array
    {
        return [
            'code' => $user->code,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'photo_path' => $user->photo_path,
            'profile_ids' => $user->profile_ids ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileSnapshot(Profile $profile): array
    {
        return [
            'code' => $profile->code,
            'name' => $profile->name,
            'section_ids' => $profile->section_ids ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function recordProductChange(Product $product, string $action, ?array $before, ?array $after, User $user): void
    {
        $this->recordChange('products', (string) $product->getKey(), $action, $before, $after, $user);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function recordUserChange(User $changedUser, string $action, ?array $before, ?array $after, User $user): void
    {
        $this->recordChange('users', (string) $changedUser->getKey(), $action, $before, $after, $user);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function recordProfileChange(Profile $profile, string $action, ?array $before, ?array $after, User $user): void
    {
        $this->recordChange('profiles', (string) $profile->getKey(), $action, $before, $after, $user);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function recordChange(string $collection, string $documentId, string $action, ?array $before, ?array $after, User $user): void
    {
        AuditLog::query()->create([
            'collection' => $collection,
            'document_id' => $documentId,
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'user_id' => (string) $user->getKey(),
            'created_at' => now(),
        ]);
    }
}
