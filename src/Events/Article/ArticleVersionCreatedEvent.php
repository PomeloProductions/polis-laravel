<?php

declare(strict_types=1);

namespace Polis\Events\Article;

use App\Models\Wiki\ArticleVersion;

/**
 * Class ArticleVersionCreatedEvent
 */
class ArticleVersionCreatedEvent
{
    /**
     * @var ArticleVersion
     */
    private $newVersion;

    /**
     * @var ArticleVersion
     */
    private $oldVersion;

    /**
     * ArticleVersionCreatedEvent constructor.
     */
    public function __construct(ArticleVersion $newVersion, ?ArticleVersion $oldVersion)
    {
        $this->newVersion = $newVersion;
        $this->oldVersion = $oldVersion;
    }

    public function getNewVersion(): ArticleVersion
    {
        return $this->newVersion;
    }

    public function getOldVersion(): ?ArticleVersion
    {
        return $this->oldVersion;
    }
}
