<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\Section;
use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Throwable;

class ApiWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureMongoIsAvailable();
        $this->clearCollections();
    }

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $this->createUser('login@example.com', 'secret-password');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'secret-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertIsString($response->json('data.access_token'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $this->createUser('login@example.com', 'secret-password');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials');
    }

    public function test_product_can_be_created(): void
    {
        $response = $this
            ->withHeaders($this->authHeadersForSection('products'))
            ->postJson('/api/products', [
                'name' => 'Producto de prueba',
                'brand' => 'Marca Uno',
                'price' => 999,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Producto de prueba')
            ->assertJsonPath('data.brand', 'Marca Uno')
            ->assertJsonPath('data.price', 999);

        $this->assertMatchesRegularExpression('/^PROD-\d{6}$/', $response->json('data.code'));
    }

    public function test_product_creation_fails_when_price_is_greater_than_999(): void
    {
        $response = $this
            ->withHeaders($this->authHeadersForSection('products'))
            ->postJson('/api/products', [
                'name' => 'Producto caro',
                'brand' => 'Marca Uno',
                'price' => 1000000,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error');

        $this->assertArrayHasKey('price', $response->json('errors'));
    }

    public function test_user_creation_fails_when_email_already_exists(): void
    {
        $context = $this->authContextForSection('users');

        $this->createUser('duplicado@example.com', 'secret-password');

        $response = $this
            ->withHeaders($context['headers'])
            ->post('/api/users', [
                'name' => 'Usuario duplicado',
                'email' => 'duplicado@example.com',
                'phone' => '+523121234567',
                'profile_ids' => [(string) $context['profile']->getKey()],
                'photo' => UploadedFile::fake()->image('photo.jpg'),
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error');

        $this->assertArrayHasKey('email', $response->json('errors'));
    }

    public function test_profile_can_be_created(): void
    {
        $headers = $this->authHeadersForSection('profiles');
        $section = $this->createSection('SEC-000099', 'users', 'Users');

        $response = $this
            ->withHeaders($headers)
            ->postJson('/api/profiles', [
                'name' => 'Perfil de prueba',
                'section_ids' => [(string) $section->getKey()],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Perfil de prueba')
            ->assertJsonPath('data.sections.0.id', (string) $section->getKey());

        $this->assertMatchesRegularExpression('/^PER-\d{6}$/', $response->json('data.code'));
    }

    public function test_user_cannot_access_an_unassigned_section(): void
    {
        $headers = $this->authHeadersForSection('users');

        $this->createSection('SEC-000001', 'products', 'Products');

        $response = $this
            ->withHeaders($headers)
            ->postJson('/api/products', [
                'name' => 'Producto no permitido',
                'brand' => 'Marca Uno',
                'price' => 100,
            ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Forbidden');
    }

    private function ensureMongoIsAvailable(): void
    {
        try {
            DB::connection('mongodb')->ping();
        } catch (Throwable $exception) {
            $this->markTestSkipped('MongoDB is not available: '.$exception->getMessage());
        }
    }

    private function clearCollections(): void
    {
        $connection = DB::connection('mongodb');

        foreach (['api_tokens', 'audit_logs', 'products', 'profiles', 'sections', 'users'] as $collection) {
            $connection->getCollection($collection)->deleteMany([]);
        }
    }

    private function createUser(string $email, string $password, array $profileIds = []): User
    {
        return User::query()->create([
            'name' => 'Usuario Test',
            'email' => $email,
            'phone' => '+523121234567',
            'photo_path' => 'users/test.jpg',
            'password' => Hash::make($password),
            'profile_ids' => $profileIds,
        ]);
    }

    private function createSection(string $code, string $key, string $name): Section
    {
        return Section::query()->create([
            'code' => $code,
            'key' => $key,
            'name' => $name,
        ]);
    }

    /**
     * @return array{headers: array<string, string>, profile: Profile, user: User, section: Section}
     */
    private function authContextForSection(string $sectionKey): array
    {
        $section = $this->createSection(
            $this->sectionCode($sectionKey),
            $sectionKey,
            ucfirst($sectionKey)
        );

        $profile = Profile::query()->create([
            'name' => ucfirst($sectionKey).' profile',
            'section_ids' => [$sectionKey],
        ]);

        $user = $this->createUser(
            "auth-{$sectionKey}@example.com",
            'secret-password',
            [(string) $profile->getKey()]
        );

        $token = app(ApiTokenService::class)->create($user);

        return [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
            ],
            'profile' => $profile,
            'user' => $user,
            'section' => $section,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function authHeadersForSection(string $sectionKey): array
    {
        return $this->authContextForSection($sectionKey)['headers'];
    }

    private function sectionCode(string $sectionKey): string
    {
        return match ($sectionKey) {
            'products' => 'SEC-000001',
            'users' => 'SEC-000002',
            'profiles' => 'SEC-000003',
            default => 'SEC-999999',
        };
    }
}
