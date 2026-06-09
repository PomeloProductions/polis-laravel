<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\User;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Polis\Models\User\ExternalAccountConnection;
use Polis\Repositories\User\ExternalAccountConnectionRepository;
use Polis\Tests\TestCase;
use Polis\Tests\Unit\Database\ExternalAccountConnectionsMigrationTest;
use Psr\Log\NullLogger;

/**
 * Unit coverage for {@see ExternalAccountConnectionRepository}.
 *
 * We exercise the repository against the in-memory sqlite connection
 * configured by phpunit.xml, building the
 * `external_account_connections` schema in setUp() rather than going
 * through the migration runner — the migration is covered separately by
 * {@see ExternalAccountConnectionsMigrationTest}.
 *
 * The User parameter on findForUserAndProvider() / findAllForUser() is
 * type-hinted against App\Models\User\User which only exists as a fixture
 * stub in this package's test harness (see tests/Fixtures/Models/User.php).
 * We assemble a User-shaped fixture via Mockery so we can set an id on it.
 */
final class ExternalAccountConnectionRepositoryTest extends TestCase
{
    private ExternalAccountConnectionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Required so the model's `encrypted:array` cast can read APP_KEY.
        // Orchestra Testbench leaves APP_KEY empty by default.
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

        Schema::create('external_account_connections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 64);
            $table->string('external_user_id', 191)->nullable();
            $table->text('credentials')->nullable();
            $table->json('scopes')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('status', 16)->default(ExternalAccountConnection::STATUS_DISCONNECTED);
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'provider']);
            $table->index(['provider', 'token_expires_at']);
        });

        $this->repository = new ExternalAccountConnectionRepository(
            new ExternalAccountConnection,
            new NullLogger,
        );
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('external_account_connections');
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Build a User-shaped Mockery so the App\Models\User\User type hint on
     * the repository methods resolves (the fixture stub for that class is
     * registered by tests/bootstrap.php).
     */
    private function userWithId(int $id): object
    {
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = $id;

        return $user;
    }

    public function test_find_for_user_and_provider_returns_match(): void
    {
        $created = $this->repository->create([
            'user_id' => 1,
            'provider' => 'github',
            'status' => ExternalAccountConnection::STATUS_CONNECTED,
        ]);

        $result = $this->repository->findForUserAndProvider($this->userWithId(1), 'github');

        $this->assertNotNull($result);
        $this->assertSame($created->id, $result->id);
        $this->assertSame('github', $result->provider);
    }

    public function test_find_for_user_and_provider_returns_null_when_user_has_no_such_link(): void
    {
        $this->repository->create([
            'user_id' => 1,
            'provider' => 'github',
            'status' => ExternalAccountConnection::STATUS_CONNECTED,
        ]);

        // User 1 has github but not discord.
        $this->assertNull(
            $this->repository->findForUserAndProvider($this->userWithId(1), 'discord')
        );
        // User 2 has nothing.
        $this->assertNull(
            $this->repository->findForUserAndProvider($this->userWithId(2), 'github')
        );
    }

    public function test_find_all_for_user_returns_every_provider(): void
    {
        $this->repository->create(['user_id' => 1, 'provider' => 'github', 'status' => 'connected']);
        $this->repository->create(['user_id' => 1, 'provider' => 'discord', 'status' => 'connected']);
        $this->repository->create(['user_id' => 2, 'provider' => 'github', 'status' => 'connected']);

        $rows = $this->repository->findAllForUser($this->userWithId(1));

        $this->assertCount(2, $rows);
        // Repository sorts by provider for stable UI rendering.
        $this->assertSame(['discord', 'github'], $rows->pluck('provider')->all());
    }

    public function test_find_all_for_user_returns_empty_when_no_rows(): void
    {
        $this->repository->create(['user_id' => 99, 'provider' => 'github', 'status' => 'connected']);

        $rows = $this->repository->findAllForUser($this->userWithId(1));

        $this->assertCount(0, $rows);
    }

    public function test_find_expiring_by_provider_returns_only_connected_expiring_rows(): void
    {
        // Past expiry, connected — should be returned.
        $expiringConnected = $this->repository->create([
            'user_id' => 1,
            'provider' => 'github',
            'status' => 'connected',
            'token_expires_at' => now()->subMinute(),
        ]);

        // Far-future expiry, connected — not returned.
        $this->repository->create([
            'user_id' => 2,
            'provider' => 'github',
            'status' => 'connected',
            'token_expires_at' => now()->addDay(),
        ]);

        // Past expiry but DISCONNECTED — not returned (no point refreshing).
        $this->repository->create([
            'user_id' => 3,
            'provider' => 'github',
            'status' => 'disconnected',
            'token_expires_at' => now()->subDay(),
        ]);

        // Past expiry, different provider — not returned.
        $this->repository->create([
            'user_id' => 4,
            'provider' => 'discord',
            'status' => 'connected',
            'token_expires_at' => now()->subMinute(),
        ]);

        // Null expiry — not returned (treated as "never expires").
        $this->repository->create([
            'user_id' => 5,
            'provider' => 'github',
            'status' => 'connected',
            'token_expires_at' => null,
        ]);

        $rows = $this->repository->findExpiringByProvider('github', now());

        $this->assertCount(1, $rows);
        $this->assertSame($expiringConnected->id, $rows->first()->id);
    }

    public function test_create_and_find_or_fail(): void
    {
        $created = $this->repository->create([
            'user_id' => 1,
            'provider' => 'github',
            'external_user_id' => '42',
            'status' => 'connected',
        ]);

        $loaded = $this->repository->findOrFail($created->id);
        $this->assertSame('github', $loaded->provider);
        $this->assertSame('42', $loaded->external_user_id);
    }

    public function test_update_persists_changes(): void
    {
        $created = $this->repository->create([
            'user_id' => 1,
            'provider' => 'github',
            'status' => 'connected',
        ]);

        $this->repository->update($created, [
            'status' => 'error',
            'last_error' => 'token revoked',
        ]);

        $loaded = $this->repository->findOrFail($created->id);
        $this->assertSame('error', $loaded->status);
        $this->assertSame('token revoked', $loaded->last_error);
    }

    public function test_delete_soft_deletes(): void
    {
        $created = $this->repository->create([
            'user_id' => 1,
            'provider' => 'github',
            'status' => 'connected',
        ]);

        $this->repository->delete($created);

        // Soft-deleted: not visible to default scope.
        $this->assertNull(ExternalAccountConnection::find($created->id));
        // But the row is still present with deleted_at set.
        $this->assertNotNull(
            DB::table('external_account_connections')->find($created->id)->deleted_at
        );
    }

    public function test_credentials_are_encrypted_at_rest_and_decrypted_on_read(): void
    {
        $payload = [
            'access_token' => 'ghp_aaaa',
            'refresh_token' => 'ghr_bbbb',
        ];

        $created = $this->repository->create([
            'user_id' => 1,
            'provider' => 'github',
            'credentials' => $payload,
            'status' => 'connected',
        ]);

        // Raw column value must NOT contain the plaintext token.
        $rawRow = DB::table('external_account_connections')->find($created->id);
        $this->assertNotNull($rawRow->credentials, 'Encrypted blob should be persisted.');
        $this->assertStringNotContainsString(
            'ghp_aaaa',
            $rawRow->credentials,
            'Plaintext access token must never appear in the credentials column.'
        );
        $this->assertStringNotContainsString(
            'ghr_bbbb',
            $rawRow->credentials,
            'Plaintext refresh token must never appear in the credentials column.'
        );

        // Re-reading through the model must decrypt back to the original payload.
        $reloaded = $this->repository->findOrFail($created->id);
        $this->assertSame($payload, $reloaded->credentials);
    }

    public function test_credentials_are_excluded_from_array_and_json_serialisation(): void
    {
        $created = $this->repository->create([
            'user_id' => 1,
            'provider' => 'github',
            'credentials' => ['access_token' => 'super-secret'],
            'status' => 'connected',
        ]);

        $array = $created->toArray();

        $this->assertArrayNotHasKey('credentials', $array, 'credentials must be hidden from serialisation.');
        $this->assertStringNotContainsString('super-secret', json_encode($array));
    }

    public function test_unique_constraint_on_user_provider_pair(): void
    {
        $this->repository->create([
            'user_id' => 1,
            'provider' => 'github',
            'status' => 'connected',
        ]);

        $this->expectException(QueryException::class);
        $this->repository->create([
            'user_id' => 1,
            'provider' => 'github',
            'status' => 'connected',
        ]);
    }
}
