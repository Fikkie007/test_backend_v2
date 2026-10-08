<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--env' => 'testing']);
    }

    public function test_user_can_register_login_create_and_list_product(): void
    {
        $this->postJson('/api/auth/register', [
            'username' => 'Api_User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()->assertJsonPath('data.username', 'Api_User');

        $login = $this->postJson('/api/auth/login', [
            'username' => 'Api_User',
            'password' => 'password123',
        ])->assertOk();

        $token = $login->json('data.access_token');

        $this->withToken($token)->postJson('/api/products', [
            'title' => 'T-Shirt',
            'price' => 99.99,
            'category' => 'Clothes',
            'images' => ['2026/10/08/tshirt.jpg'],
        ])->assertCreated()->assertJsonPath('data.images.0', '2026/10/08/tshirt.jpg');

        $this->getJson('/api/products?category=Clothes&limit=10')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'T-Shirt')
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_refresh_token_rotates_and_replay_is_rejected(): void
    {
        $this->postJson('/api/auth/register', [
            'username' => 'Refresh_User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'username' => 'Refresh_User',
            'password' => 'password123',
        ])->assertOk();

        $oldRefresh = $login->json('data.refresh_token');
        $this->postJson('/api/auth/refresh', ['refresh_token' => $oldRefresh])
            ->assertOk()
            ->assertJsonPath('code', 200);

        $this->postJson('/api/auth/refresh', ['refresh_token' => $oldRefresh])
            ->assertUnauthorized()
            ->assertJsonPath('code', 401);
    }

    public function test_duplicate_username_returns_conflict(): void
    {
        $payload = [
            'username' => 'Duplicate_User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->postJson('/api/auth/register', $payload)->assertCreated();
        $this->postJson('/api/auth/register', $payload)->assertStatus(409)->assertJsonPath('code', 409);
    }

    public function test_product_mutation_requires_authentication_and_validation_uses_400(): void
    {
        $this->postJson('/api/products', [])->assertUnauthorized();

        $this->postJson('/api/auth/register', [
            'username' => 'Validation_User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $login = $this->postJson('/api/auth/login', [
            'username' => 'Validation_User',
            'password' => 'password123',
        ]);

        $this->withToken($login->json('data.access_token'))
            ->postJson('/api/products', ['title' => 'Missing image'])
            ->assertStatus(400)
            ->assertJsonPath('code', 400);

        $this->withToken($login->json('data.access_token'))
            ->postJson('/api/products', [
                'title' => 'Unsafe image',
                'price' => 1,
                'category' => 'Test',
                'images' => ['2026/10/08/../secret.jpg'],
            ])->assertStatus(400)->assertJsonPath('code', 400);
    }

    public function test_put_preserves_or_replaces_images_and_records_audit_user(): void
    {
        $this->postJson('/api/auth/register', [
            'username' => 'Audit_User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $login = $this->postJson('/api/auth/login', [
            'username' => 'Audit_User',
            'password' => 'password123',
        ]);
        $token = $login->json('data.access_token');

        $created = $this->withToken($token)->postJson('/api/products', [
            'title' => 'Original',
            'price' => 10,
            'category' => 'Test',
            'images' => ['2026/10/08/original.jpg'],
        ])->assertCreated();
        $id = $created->json('data.id');

        $this->withToken($token)->putJson('/api/products/'.$id, [
            'title' => 'Retained image',
            'price' => 11,
            'category' => 'Test',
        ])->assertOk()->assertJsonPath('data.images.0', '2026/10/08/original.jpg');

        $this->withToken($token)->putJson('/api/products/'.$id, [
            'title' => 'Replaced image',
            'price' => 12,
            'category' => 'Test',
            'images' => ['2026/10/08/replacement.jpg'],
        ])->assertOk()->assertJsonPath('data.images.0', '2026/10/08/replacement.jpg')->assertJsonPath('data.updated_by.username', 'Audit_User');

        $this->withToken($token)->deleteJson('/api/products/'.$id)->assertNoContent();
    }

    public function test_username_login_is_case_sensitive(): void
    {
        $this->postJson('/api/auth/register', [
            'username' => 'Case_User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->postJson('/api/auth/login', [
            'username' => 'case_user',
            'password' => 'password123',
        ])->assertUnauthorized();
    }
}
