<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\User;

use App\Models\User\Contact;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\User\ContactRepository;
use Polis\Repositories\User\UserRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ContactRepositoryTest
 */
final class ContactRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var Hasher
     */
    private $hasher;

    /**
     * @var UserRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->hasher = $this->app->make(Hasher::class);

        $this->repository = new ContactRepository(
            new Contact,
            $this->getGenericLogMock(),
        );
    }

    public function test_find_all_success(): void
    {
        Contact::factory()->count(5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function test_find_all_success_with_user(): void
    {
        $user = User::factory()->create();

        Contact::factory()->count(5)->create();

        Contact::factory()->count(4)->create([
            'requested_id' => $user->id,
        ]);
        Contact::factory()->count(3)->create([
            'initiated_by_id' => $user->id,
        ]);

        $items = $this->repository->findAll([], [], [], [], 10, [$user]);
        $this->assertCount(7, $items);
    }

    public function test_find_all_empty(): void
    {
        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function test_find_or_fail_success(): void
    {
        $model = Contact::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        Contact::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        $initiatedBy = User::factory()->create();
        $requested = User::factory()->create();

        /** @var Contact $contact */
        $contact = $this->repository->create([
            'initiated_by_id' => $initiatedBy->id,
            'requested_id' => $requested->id,
        ]);

        $this->assertEquals(1, Contact::count());
        $this->assertEquals($initiatedBy->id, $contact->initiated_by_id);
        $this->assertEquals($requested->id, $contact->requested_id);
    }

    public function test_update_success(): void
    {
        $model = Contact::factory()->create();
        $this->repository->update($model, [
            'denied_at' => Carbon::now(),
        ]);

        $updated = Contact::find($model->id);
        $this->assertNotNull($updated->denied_at);
    }

    public function test_delete_success(): void
    {
        $model = Contact::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(Contact::find($model->id));
    }
}
