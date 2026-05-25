<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Validators\Test;

use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleIteration;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Polis\Contracts\Repositories\Wiki\ArticleIterationRepositoryContract;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;
use Polis\Validators\ArticleVersion\SelectedIterationBelongsToArticleValidator;

/**
 * Class SelectedIterationBelongsToArticleValidatorTest
 */
final class SelectedIterationBelongsToArticleValidatorTest extends TestCase
{
    /**
     * @var CustomMockInterface|ArticleIterationRepositoryContract
     */
    private $repository;

    /**
     * @var CustomMockInterface|Request
     */
    private $request;

    /**
     * @var SelectedIterationBelongsToArticleValidator
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = mock(ArticleIterationRepositoryContract::class);
        $this->request = mock(Request::class);

        $this->validator = new SelectedIterationBelongsToArticleValidator(
            $this->request,
            $this->repository,
        );
    }

    public function test_validate_passes_question_option_id_not_set(): void
    {
        $this->assertTrue($this->validator->validate('article_iteration_id', null));
    }

    public function test_validate_fails_question_id_not_set(): void
    {
        $this->request->shouldReceive('route')->once()->with('article', null)->andReturn(null);

        $this->assertFalse($this->validator->validate('article_iteration_id', 332));
    }

    public function test_validate_fails_question_option_not_found(): void
    {
        $article = new Article;
        $article->id = 453;
        $this->request->shouldReceive('route')->once()->with('article', null)->andReturn($article);
        $this->repository->shouldReceive('findOrFail')->once()->andThrow(ModelNotFoundException::class);

        $this->assertFalse($this->validator->validate('article_iteration_id', 332));
    }

    public function test_validate_fails_question_option_and_question_id_does_not_match(): void
    {
        $article = new Article;
        $article->id = 453;
        $this->request->shouldReceive('route')->once()->with('article', null)->andReturn($article);
        $this->repository->shouldReceive('findOrFail')->once()->andReturn(new ArticleIteration([
            'article_id' => 454,
        ]));

        $this->assertFalse($this->validator->validate('article_iteration_id', 332));
    }

    public function test_validate_passes(): void
    {
        $article = new Article;
        $article->id = 453;
        $this->request->shouldReceive('route')->once()->with('article', null)->andReturn($article);
        $this->repository->shouldReceive('findOrFail')->once()->andReturn(new ArticleIteration([
            'article_id' => 453,
        ]));

        $this->assertTrue($this->validator->validate('article_iteration_id', 332));
    }
}
