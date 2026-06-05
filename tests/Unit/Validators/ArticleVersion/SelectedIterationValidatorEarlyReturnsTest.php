<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Validators\ArticleVersion;

use Illuminate\Http\Request;
use Mockery;
use Polis\Contracts\Repositories\Wiki\ArticleIterationRepositoryContract;
use Polis\Tests\TestCase;
use Polis\Validators\ArticleVersion\SelectedIterationBelongsToArticleValidator;

/**
 * Covers the early-return branches of SelectedIterationBelongsToArticleValidator
 * that don't require materializing an App\Models\Wiki\ArticleIteration:
 *   - empty/null value short-circuits to true
 *   - missing 'article' route binding short-circuits to false
 * Full coverage of the success path requires consuming an Eloquent
 * ArticleIteration which depends on the consumer-app and lives in
 * Consumer-Only.
 */
final class SelectedIterationValidatorEarlyReturnsTest extends TestCase
{
    public function test_returns_true_when_value_is_falsy(): void
    {
        $request = Mockery::mock(Request::class);
        $repo = Mockery::mock(ArticleIterationRepositoryContract::class);
        $repo->shouldNotReceive('findOrFail');

        $validator = new SelectedIterationBelongsToArticleValidator($request, $repo);

        $this->assertTrue($validator->validate('article_iteration_id', null));
        $this->assertTrue($validator->validate('article_iteration_id', 0));
        $this->assertTrue($validator->validate('article_iteration_id', ''));
    }

    public function test_returns_false_when_no_article_in_route(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('article', null)->andReturn(null);

        $repo = Mockery::mock(ArticleIterationRepositoryContract::class);
        $repo->shouldNotReceive('findOrFail');

        $validator = new SelectedIterationBelongsToArticleValidator($request, $repo);

        $this->assertFalse($validator->validate('article_iteration_id', 42));
    }

    public function test_throws_when_attribute_is_not_article_iteration_id(): void
    {
        $request = Mockery::mock(Request::class);
        $repo = Mockery::mock(ArticleIterationRepositoryContract::class);

        $validator = new SelectedIterationBelongsToArticleValidator($request, $repo);

        $this->expectException(\RuntimeException::class);

        $validator->validate('wrong_attribute', 'value');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
