<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\User;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\PendingMail;
use Mockery;
use Polis\Events\User\SignUpEvent;
use Polis\Listeners\User\SignUpListener;
use Polis\Mail\TemplatedMailable;
use Polis\Tests\TestCase;

/**
 * Standalone coverage for the Polis-namespaced SignUpListener (the
 * existing SignUpListenerTest.php imports App\Listeners\User\SignUpListener
 * which is consumer-app, and lives in the Consumer-Only suite).
 *
 * The listener dispatches a TemplatedMailable("welcome") via the Mailer
 * to the newly-registered user's email. App\Models\User\User is stubbed
 * via Mockery — the listener only reads $user->first_name,
 * $user->last_name, and $user->email as dynamic properties.
 */
final class SignUpListenerStandaloneTest extends TestCase
{
    public function test_handle_sends_templated_welcome_email_to_user(): void
    {
        // Use a real fixture User instance (which now extends
        // BaseModelAbstract) rather than a Mockery double — Mockery wraps
        // Eloquent's ArrayAccess implementation in a way that requires
        // stubbing offsetExists (triggered by `$user->first_name ??
        // ''`), and a concrete instance handles all of that
        // automatically.
        $userClass = 'App\\Models\\User\\User';
        $user = new $userClass;
        $user->first_name = 'Ada';
        $user->last_name = 'Lovelace';
        $user->email = 'ada@example.com';

        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')
            ->once()
            ->withArgs(function (TemplatedMailable $m) {
                return $m->templateKey === 'welcome'
                    && $m->variables['user']['first_name'] === 'Ada'
                    && $m->variables['user']['last_name'] === 'Lovelace'
                    && $m->variables['user']['email'] === 'ada@example.com'
                    && $m->variables['app']['name'] === 'TestApp';
            });

        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('to')->once()->with('ada@example.com')->andReturn($pending);

        $config = Mockery::mock(Repository::class);
        $config->shouldReceive('get')->with('app.name', 'Polis')->andReturn('TestApp');

        $listener = new SignUpListener($mailer, $config);
        $listener->handle(new SignUpEvent($user));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
