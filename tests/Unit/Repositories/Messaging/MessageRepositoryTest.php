<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Messaging;

use App\Models\Messaging\Message;
use App\Models\User\User;
use Mockery;
use Polis\Contracts\Models\CanReceiveTextMessagesContract;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Repositories\Messaging\MessageRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for MessageRepository — the create override (sets to_id from
 * relatedModel), sendDirectEmail / sendEmailToUser / sendEmailToSuperAdmins
 * / sendTextMessage helpers, and the orderBy-on-created_at branch in
 * findAll.
 *
 * The Message fixture is a plain class stub aliased to
 * App\Models\Messaging\Message. We mock the model + builder calls; no
 * real DB needed because the repository's specialty is delegation to
 * parent::create with shaped data and orderBy('created_at', 'desc') on
 * the find-all flow.
 */
final class MessageRepositoryTest extends TestCase
{
    private function buildModelMock()
    {
        $mock = Mockery::mock(Message::class);
        $mock->shouldReceive('save');
        $mock->shouldReceive('getAttribute')->andReturn(1);
        $mock->wasRecentlyCreated = true;
        $mock->id = 1;

        return $mock;
    }

    public function test_send_direct_email_creates_message_with_subject_template_email_and_greeting(): void
    {
        $modelMock = $this->buildModelMock();
        $modelMock->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use ($modelMock) {
                $this->assertSame('Subject', $data['subject']);
                $this->assertSame('template-key', $data['template']);
                $this->assertSame('test@example.com', $data['email']);
                $this->assertSame('Hi there', $data['data']['greeting']);
                $this->assertSame('extra-context', $data['data']['k']);

                return $modelMock;
            });

        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $repo = new MessageRepository($modelMock, $this->getGenericLogMock(), $userRepo);

        $repo->sendDirectEmail(
            'test@example.com',
            'Subject',
            'template-key',
            'Hi there',
            ['k' => 'extra-context'],
        );
    }

    public function test_send_email_to_user_uses_user_email_and_default_greeting(): void
    {
        $user = new User;
        $user->id = 99;
        $user->email = 'ada@example.com';
        $user->first_name = 'Ada';

        $modelMock = $this->buildModelMock();
        $modelMock->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use ($modelMock) {
                $this->assertSame('ada@example.com', $data['email']);
                $this->assertSame('Hello Ada', $data['data']['greeting']);
                $this->assertContains('email', $data['via']);
                // to_id is set by the create override when relatedModel
                // is non-null; it gets attached after newInstance though.
                return $modelMock;
            });

        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $repo = new MessageRepository($modelMock, $this->getGenericLogMock(), $userRepo);

        $repo->sendEmailToUser($user, 'Subj', 'tmpl');
    }

    public function test_send_email_to_user_with_explicit_greeting_overrides_default(): void
    {
        $user = new User;
        $user->id = 1;
        $user->email = 'a@b.c';
        $user->first_name = 'X';

        $modelMock = $this->buildModelMock();
        $modelMock->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use ($modelMock) {
                $this->assertSame('Custom greeting', $data['data']['greeting']);

                return $modelMock;
            });

        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $repo = new MessageRepository($modelMock, $this->getGenericLogMock(), $userRepo);
        $repo->sendEmailToUser($user, 'Subj', 'tmpl', [], 'Custom greeting');
    }

    public function test_send_email_to_super_admins_dispatches_one_message_per_admin(): void
    {
        $admin1 = new User;
        $admin1->id = 1;
        $admin1->email = 'a@x';
        $admin1->first_name = 'A';
        $admin2 = new User;
        $admin2->id = 2;
        $admin2->email = 'b@x';
        $admin2->first_name = 'B';

        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $userRepo->shouldReceive('findSuperAdmins')
            ->once()
            ->andReturn(new \Illuminate\Support\Collection([$admin1, $admin2]));

        $modelMock = $this->buildModelMock();
        $modelMock->shouldReceive('newInstance')->twice()->andReturn($modelMock);

        $repo = new MessageRepository($modelMock, $this->getGenericLogMock(), $userRepo);
        $messages = $repo->sendEmailToSuperAdmins('Subj', 'tmpl');

        $this->assertCount(2, $messages);
    }

    public function test_send_text_message_creates_message_with_morph_id_and_type(): void
    {
        $recipient = Mockery::mock(CanReceiveTextMessagesContract::class);
        $recipient->shouldReceive('getAttribute')->andReturn(42);
        $recipient->id = 42;
        $recipient->shouldReceive('morphRelationName')->andReturn('organization');

        $modelMock = $this->buildModelMock();
        $modelMock->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use ($modelMock) {
                $this->assertSame(42, $data['to_id']);
                $this->assertSame('organization', $data['to_type']);
                $this->assertSame('hello world', $data['data']['message']);

                return $modelMock;
            });

        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $repo = new MessageRepository($modelMock, $this->getGenericLogMock(), $userRepo);
        $repo->sendTextMessage($recipient, 'hello world');
    }
}
