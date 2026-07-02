<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the default API administrator user.
     */
    public function run(): void
    {
        $sectionIds = Section::query()
            ->whereIn('key', ['products', 'users', 'profiles'])
            ->get()
            ->map(fn (Section $section): string => (string) $section->getKey())
            ->values()
            ->all();

        $profile = Profile::query()->updateOrCreate(
            ['code' => 'PER-000001'],
            [
                'name' => 'Administrator',
                'section_ids' => $sectionIds,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'phone' => '+523121234567',
                'photo_path' => 'users/admin.jpg',
                'password' => Hash::make('password'),
                'profile_ids' => [(string) $profile->getKey()],
            ]
        );
    }
}
