<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Seed the base API sections.
     */
    public function run(): void
    {
        $sections = [
            [
                'code' => 'SEC-000001',
                'name' => 'Products',
                'key' => 'products',
            ],
            [
                'code' => 'SEC-000002',
                'name' => 'Users',
                'key' => 'users',
            ],
            [
                'code' => 'SEC-000003',
                'name' => 'Profiles',
                'key' => 'profiles',
            ],
        ];

        foreach ($sections as $section) {
            Section::query()->updateOrCreate(
                ['key' => $section['key']],
                $section
            );
        }
    }
}
