<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ProfileStoreRequest;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Http\Resources\ProfileDetailResource;
use App\Http\Resources\ProfileListResource;
use App\Models\Profile;
use App\Models\Section;
use App\Services\AuditLogService;
use App\Services\ProfileExportService;
use App\Support\ApiResponse;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfileController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profiles = Profile::query()
            ->orderByDesc('created_at')
            ->paginate(15);

        $profiles->getCollection()->each(function (Profile $profile): void {
            $profile->sections = $this->sectionsForProfile($profile);
        });

        return ApiResponse::success('Profiles retrieved', [
            'items' => ProfileListResource::collection($profiles->getCollection())->resolve($request),
            'pagination' => [
                'current_page' => $profiles->currentPage(),
                'last_page' => $profiles->lastPage(),
                'per_page' => $profiles->perPage(),
                'total' => $profiles->total(),
            ],
        ]);
    }

    public function store(ProfileStoreRequest $request): JsonResponse
    {
        $profile = Profile::query()->create($request->validated());
        $profile->sections = $this->sectionsForProfile($profile);

        return ApiResponse::success(
            'Profile created',
            ProfileDetailResource::make($profile)->resolve($request),
            201
        );
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $profile = $this->findProfile($id);

        if (! $profile) {
            return ApiResponse::error('Profile not found', [], 404);
        }

        $profile->sections = $this->sectionsForProfile($profile);

        return ApiResponse::success(
            'Profile retrieved',
            ProfileDetailResource::make($profile)->resolve($request)
        );
    }

    public function update(ProfileUpdateRequest $request, string $id, AuditLogService $auditLogs): JsonResponse
    {
        $profile = $this->findProfile($id);

        if (! $profile) {
            return ApiResponse::error('Profile not found', [], 404);
        }

        $before = $auditLogs->profileSnapshot($profile);

        $profile->fill($request->validated());
        $profile->save();
        $profile->refresh();

        $auditLogs->recordProfileChange(
            $profile,
            'update',
            $before,
            $auditLogs->profileSnapshot($profile),
            $request->user()
        );

        $profile->sections = $this->sectionsForProfile($profile);

        return ApiResponse::success(
            'Profile updated',
            ProfileDetailResource::make($profile)->resolve($request)
        );
    }

    public function destroy(Request $request, string $id, AuditLogService $auditLogs): JsonResponse
    {
        $profile = $this->findProfile($id);

        if (! $profile) {
            return ApiResponse::error('Profile not found', [], 404);
        }

        $auditLogs->recordProfileChange(
            $profile,
            'delete',
            $auditLogs->profileSnapshot($profile),
            null,
            $request->user()
        );

        $profile->delete();

        return ApiResponse::success('Profile deleted');
    }

    public function exportPdf(ProfileExportService $exports): Responsable
    {
        $profiles = Profile::query()
            ->orderBy('created_at')
            ->get();

        return $exports->pdf($profiles);
    }

    public function exportExcel(ProfileExportService $exports): BinaryFileResponse
    {
        $profiles = Profile::query()
            ->orderBy('created_at')
            ->get();

        return $exports->excel($profiles);
    }

    private function findProfile(string $id): ?Profile
    {
        return Profile::query()->find($id);
    }

    /**
     * @return Collection<int, array{id: string, key: string|null, name: string|null}>
     */
    private function sectionsForProfile(Profile $profile): Collection
    {
        $sectionIds = collect($profile->section_ids ?? [])
            ->filter()
            ->map(fn (mixed $sectionId): string => (string) $sectionId)
            ->values();

        if ($sectionIds->isEmpty()) {
            return collect();
        }

        return Section::query()
            ->whereIn('_id', $sectionIds->all())
            ->get()
            ->map(fn (Section $section): array => [
                'id' => (string) $section->getKey(),
                'key' => $section->key,
                'name' => $section->name,
            ])
            ->values();
    }
}
