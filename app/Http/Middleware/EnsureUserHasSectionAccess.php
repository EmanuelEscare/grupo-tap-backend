<?php

namespace App\Http\Middleware;

use App\Models\Profile;
use App\Models\Section;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasSectionAccess
{
    public function handle(Request $request, Closure $next, ?string $sectionKey = null): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Unauthenticated', [], 401);
        }

        $sectionIdentifier = $sectionKey ?? $this->routeSectionIdentifier($request);

        if (! $sectionIdentifier) {
            return ApiResponse::error('Section not found', [], 404);
        }

        $section = Section::query()
            ->where('_id', $sectionIdentifier)
            ->orWhere('code', $sectionIdentifier)
            ->orWhere('key', $sectionIdentifier)
            ->first();

        if (! $section) {
            return ApiResponse::error('Section not found', [], 404);
        }

        $profileIds = collect($user->profile_ids ?? [])
            ->filter()
            ->map(fn (mixed $profileId): string => (string) $profileId)
            ->values();

        if ($profileIds->isEmpty()) {
            return ApiResponse::error('Forbidden', [], 403);
        }

        $allowedSectionIdentifiers = Profile::query()
            ->whereIn('_id', $profileIds->all())
            ->get()
            ->flatMap(fn (Profile $profile): array => $profile->section_ids ?? [])
            ->filter()
            ->map(fn (mixed $allowedSectionId): string => (string) $allowedSectionId)
            ->unique();

        $requestedSectionIdentifiers = collect([
            (string) $section->getKey(),
            (string) $section->code,
            (string) $section->key,
        ])->filter();

        if ($allowedSectionIdentifiers->intersect($requestedSectionIdentifiers)->isEmpty()) {
            return ApiResponse::error('Forbidden', [], 403);
        }

        return $next($request);
    }

    private function routeSectionIdentifier(Request $request): ?string
    {
        foreach (['section', 'section_key', 'key'] as $parameter) {
            $value = $request->route($parameter);

            if ($value instanceof Section) {
                return (string) $value->getKey();
            }

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
