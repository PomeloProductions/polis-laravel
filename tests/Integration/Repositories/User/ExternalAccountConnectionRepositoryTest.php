<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\User;

use App\Models\User\ExternalAccountConnection;
use App\Models\User\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Polis\Models\User\ExternalAccountConnection as PolisExternalAccountConnection;
use Polis\Repositories\User\ExternalAccountConnectionRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Integration coverage for {@see ExternalAccountConnectionRepository}.
 *
 * Exercises the repository against the real dummy-app database (the
 * `external_account_connections` table created by the package's own migration,
 * loaded by {@see ApplicationTestCase::defineDatabaseMigrations()}). This
 * supersedes the former DB-backed Unit test, which built an ad-hoc sqlite
 * schema in setUp() and assembled a Mockery User — both standard violations
 * (the Unit suite must be pure). Here we use real {@see User} factory rows and
 * the real App model so encryption-at-rest, soft-deletes and the composite
 * unique index are all verified end to end.
 */
final class ExternalAccountConnectionRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private ExternalAccountConnectionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new ExternalAccountConnectionRepository(
            new ExternalAccountConnection,
            $this->getGenericLogMock(),
        );
    }

    public function test_find_for_user_and_provider_returns_match(): void
    {
        $user = User::factory()->create();

        $created = $this->repository->create([
            'user_id' => $user->id,
            'provider' => 'github',
            'status' => PolisExternalAccountConnection::STATUS_CONNECTED,
        ]);

        $result = $this->repository->findForUserAndProvider($user, 'github');

        $this->assertNotNull($result);
        $this->assertSame($created->id, $result->id);
        $this->assertSame('github', $result->provider);
    }

    public function test_find_for_user_and_provider_returns_null_when_user_has_no_such_link(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->repository->create([
            'user_id' => $user->id,
            'provider' => 'github',
            'status' => PolisExternalAccountConnection::STATUS_CONNECTED,
        ]);

        // User has github but not discord.
        $this->assertNull(
            $this->repository->findForUserAndProvider($user, 'discord')
        );
        // Other user has nothing.
        $this->assertNull(
            $this->repository->findForUserAndProvider($otherUser, 'github')
        );
    }

    public function test_find_all_for_user_returns_every_provider(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->repository->create(['user_id' => $user->id, 'provider' => 'github', 'status' => 'connected']);
        $this->repository->create(['user_id' => $user->id, 'provider' => 'discord', 'status' => 'connected']);
        $this->repository->create(['user_id' => $otherUser->id, 'provider' => 'github', 'status' => 'connected']);

        $rows = $this->repository->findAllForUser($user);

        $this->assertCount(2, $rows);
        // Repository sorts by provider for stable UI rendering.
        $this->assertSame(['discord', 'github'], $rows->pluck('provider')->all());
    }

    public function test_find_all_for_user_returns_empty_when_no_rows(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->repository->create(['user_id' => $otherUser->id, 'provider' => 'github', 'status' => 'connected']);

        $rows = $this->repository->findAllForUser($user);

        $this->assertCount(0, $rows);
    }

    public function test_find_expiring_by_provider_returns_only_connected_expiring_rows(): void
    {
        // Past expiry, connected — should be returned.
        $expiringConnected = $this->repository->create([
            'user_id' => User::factory()->create()->id,
            'provider' => 'github',
            'status' => 'connected',
            'token_expires_at' => now()->subMinute(),
        ]);

        // Far-future expiry, connected — not returned.
        $this->repository->create([
            'user_id' => User::factory()->create()->id,
            'provider' => 'github',
            'status' => 'connected',
            'token_expires_at' => now()->addDay(),
        ]);

        // Past expiry but DISCONNECTED — not returned (no point refreshing).
        $this->repository->create([
            'user_id' => User::factory()->create()->id,
            'provider' => 'github',
            'status' => 'disconnected',
            'token_expires_at' => now()->subDay(),
        ]);

        // Past expiry, different provider — not returned.
        $this->repository->create([
            'user_id' => User::factory()->create()->id,
            'provider' => 'discord',
            'status' => 'connected',
            'token_expires_at' => now()->subMinute(),
        ]);

        // Null expiry — not returned (treated as "never expires").
        $this->repository->create([
            'user_id' => User::factory()->create()->id,
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
        $user = User::factory()->create();

        $created = $this->repository->create([
            'user_id' => $user->id,
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
        $user = User::factory()->create();

        $created = $this->repository->create([
            'user_id' => $user->id,
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
        $user = User::factory()->create();

        $created = $this->repository->create([
            'user_id' => $user->id,
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
        $user = User::factory()->create();

        $payload = [
            'access_token' => 'ghp_aaaa',
            'refresh_token' => 'ghr_bbbb',
        ];

        $created = $this->repository->create([
            'user_id' => $user->id,
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
        $user = User::factory()->create();

        $created = $this->repository->create([
            'user_id' => $user->id,
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
        $user = User::factory()->create();

        $this->repository->create([
            'user_id' => $user->id,
            'provider' => 'github',
            'status' => 'connected',
        ]);

        $this->expectException(QueryException::class);
        $this->repository->create([
            'user_id' => $user->id,
            'provider' => 'github',
            'status' => 'connected',
        ]);
    }
}
