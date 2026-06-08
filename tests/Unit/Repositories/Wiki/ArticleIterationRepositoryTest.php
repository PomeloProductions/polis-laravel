<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Wiki;

use App\Models\Wiki\ArticleIteration;
use Mockery;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Repositories\Wiki\ArticleIterationRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for ArticleIterationRepository — instantiation and the two
 * NotImplemented traits (Delete and Update).
 */
final class ArticleIterationRepositoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! class_exists(ArticleIteration::class, false)) {
            eval('namespace App\\Models\\Wiki; class ArticleIteration extends \\Polis\\Models\\BaseModelAbstract {}');
        }
    }

    public function test_extends_base_repository_abstract(): void
    {
        $repo = new ArticleIterationRepository(
            new ArticleIteration,
            $this->getGenericLogMock(),
        );
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_delete_throws_not_implemented(): void
    {
        $repo = new ArticleIterationRepository(new ArticleIteration, $this->getGenericLogMock());
        $this->expectException(NotImplementedException::class);
        $repo->delete(Mockery::mock(\Polis\Models\BaseModelAbstract::class));
    }

    public function test_update_throws_not_implemented(): void
    {
        $repo = new ArticleIterationRepository(new ArticleIteration, $this->getGenericLogMock());
        $this->expectException(NotImplementedException::class);
        $repo->update(Mockery::mock(\Polis\Models\BaseModelAbstract::class), []);
    }
}
