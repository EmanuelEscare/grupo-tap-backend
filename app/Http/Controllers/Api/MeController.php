<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SectionResource;
use App\Http\Resources\UserResource;
use App\Models\Profile;
use App\Models\Section;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Me
 * 
 * This controller manage endpoints related to the authenticated user, 
 * that is, "me" as a user who has already logged in
 */
class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(
            'Authenticated user retrieved',
            UserResource::make($request->user())->resolve($request)
        );
    }

    public function sections(Request $request): JsonResponse
    {
        $profileIds = collect($request->user()->profile_ids ?? [])
            ->filter()
            ->map(fn (mixed $profileId): string => (string) $profileId)
            ->values();

        if ($profileIds->isEmpty()) {
            return ApiResponse::success('User sections retrieved', [
                'sections' => [],
            ]);
        }

        $sectionIds = Profile::query()
            ->whereIn('_id', $profileIds->all())
            ->get()
            ->flatMap(fn (Profile $profile): array => $profile->section_ids ?? [])
            ->filter()
            ->map(fn (mixed $sectionId): string => (string) $sectionId)
            ->unique()
            ->values();

        if ($sectionIds->isEmpty()) {
            return ApiResponse::success('User sections retrieved', [
                'sections' => [],
            ]);
        }

        $sections = Section::query()
            ->whereIn('_id', $sectionIds->all())
            ->orWhereIn('code', $sectionIds->all())
            ->orWhereIn('key', $sectionIds->all())
            ->orderBy('name')
            ->get();

        return ApiResponse::success('User sections retrieved', [
            'sections' => SectionResource::collection($sections)->resolve($request),
        ]);
    }
}
