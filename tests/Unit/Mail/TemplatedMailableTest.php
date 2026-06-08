<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Mail;

use Illuminate\Container\Container;
use Mockery;
use Polis\Contracts\Services\Messaging\EmailTemplateRenderingServiceContract;
use Polis\Mail\RenderedEmail;
use Polis\Mail\TemplatedMailable;
use Polis\Tests\TestCase;

/**
 * Exercises TemplatedMailable's deferred-resolution build() path: it
 * delegates to the bound EmailTemplateRenderingService and then sets
 * the Mailable's subject + HTML body.
 */
final class TemplatedMailableTest extends TestCase
{
    public function test_constructor_stores_inputs(): void
    {
        $mailable = new TemplatedMailable(
            templateKey: 'welcome',
            variables: ['user' => ['first_name' => 'Ada']],
            organizationId: 7,
        );

        $this->assertSame('welcome', $mailable->templateKey);
        $this->assertSame(['user' => ['first_name' => 'Ada']], $mailable->variables);
        $this->assertSame(7, $mailable->organizationId);
    }

    public function test_organization_id_defaults_to_null(): void
    {
        $mailable = new TemplatedMailable(
            templateKey: 'welcome',
            variables: [],
        );

        $this->assertNull($mailable->organizationId);
    }

    public function test_build_resolves_via_rendering_service(): void
    {
        $service = Mockery::mock(EmailTemplateRenderingServiceContract::class);
        $service->shouldReceive('render')
            ->once()
            ->with('welcome', ['user' => ['first_name' => 'Ada']], 7)
            ->andReturn(new RenderedEmail('Welcome Ada', '<p>Hi Ada</p>'));

        Container::setInstance($container = new Container);
        $container->instance(EmailTemplateRenderingServiceContract::class, $service);

        $mailable = new TemplatedMailable(
            templateKey: 'welcome',
            variables: ['user' => ['first_name' => 'Ada']],
            organizationId: 7,
        );

        $result = $mailable->build();

        $this->assertSame($mailable, $result);
        $this->assertSame('Welcome Ada', $mailable->subject);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Container::setInstance(null);
        parent::tearDown();
    }
}
