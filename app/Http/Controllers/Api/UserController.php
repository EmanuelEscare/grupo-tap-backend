<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\UserDetailResource;
use App\Http\Resources\UserListResource;
use App\Models\Profile;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\UserExportService;
use App\Support\ApiResponse;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->paginate(15);

        return ApiResponse::success('Users retrieved', [
            'items' => UserListResource::collection($users->getCollection())->resolve($request),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function store(UserStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $temporaryPassword = Str::password(16);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'photo_path' => $request->file('photo')->store('users', 'public'),
            'password' => Hash::make($temporaryPassword),
            'profile_ids' => $validated['profile_ids'],
        ]);

        Mail::raw(
            "Tu contraseña temporal es: {$temporaryPassword}\n\nCámbiala después de iniciar sesión.",
            function (Message $message) use ($user): void {
                $message
                    ->to($user->email)
                    ->subject('Contraseña temporal');
            }
        );

        $user->profiles = $this->profilesForUser($user);

        return ApiResponse::success(
            'User created',
            UserDetailResource::make($user)->resolve($request),
            201
        );
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $this->findUser($id);

        if (! $user) {
            return ApiResponse::error('User not found', [], 404);
        }

        $user->profiles = $this->profilesForUser($user);

        return ApiResponse::success(
            'User retrieved',
            UserDetailResource::make($user)->resolve($request)
        );
    }

    public function update(UserUpdateRequest $request, string $id, AuditLogService $auditLogs): JsonResponse
    {
        $user = $this->findUser($id);

        if (! $user) {
            return ApiResponse::error('User not found', [], 404);
        }

        $before = $auditLogs->userSnapshot($user);
        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('users', 'public');
        }

        unset($validated['photo']);

        $user->fill($validated);
        $user->save();
        $user->refresh();

        $auditLogs->recordUserChange(
            $user,
            'update',
            $before,
            $auditLogs->userSnapshot($user),
            $request->user()
        );

        $user->profiles = $this->profilesForUser($user);

        return ApiResponse::success(
            'User updated',
            UserDetailResource::make($user)->resolve($request)
        );
    }

    public function destroy(Request $request, string $id, AuditLogService $auditLogs): JsonResponse
    {
        $user = $this->findUser($id);

        if (! $user) {
            return ApiResponse::error('User not found', [], 404);
        }

        $auditLogs->recordUserChange(
            $user,
            'delete',
            $auditLogs->userSnapshot($user),
            null,
            $request->user()
        );

        $user->delete();

        return ApiResponse::success('User deleted');
    }

    public function exportPdf(UserExportService $exports): Responsable
    {
        $users = User::query()
            ->orderBy('created_at')
            ->get();

        return $exports->pdf($users);
    }

    public function exportExcel(UserExportService $exports): BinaryFileResponse
    {
        $users = User::query()
            ->orderBy('created_at')
            ->get();

        return $exports->excel($users);
    }

    private function findUser(string $id): ?User
    {
        return User::query()->find($id);
    }

    /**
     * @return Collection<int, array{id: string, code: string|null, name: string|null}>
     */
    private function profilesForUser(User $user): Collection
    {
        $profileIds = collect($user->profile_ids ?? [])
            ->filter()
            ->map(fn (mixed $profileId): string => (string) $profileId)
            ->values();

        if ($profileIds->isEmpty()) {
            return collect();
        }

        return Profile::query()
            ->whereIn('_id', $profileIds->all())
            ->get()
            ->map(fn (Profile $profile): array => [
                'id' => (string) $profile->getKey(),
                'code' => $profile->code,
                'name' => $profile->name,
            ])
            ->values();
    }
}
