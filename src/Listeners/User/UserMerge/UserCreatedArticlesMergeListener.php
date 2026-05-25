<?php

declare(strict_types=1);

namespace Polis\Listeners\User\UserMerge;

use Polis\Contracts\Repositories\Wiki\ArticleRepositoryContract;
use Polis\Events\User\UserMergeEvent;

/**
 * Class UserCreatedArticlesMergeListener
 */
class UserCreatedArticlesMergeListener
{
    /**
     * @var ArticleRepositoryContract
     */
    private $articleRepository;

    /**
     * UserCreatedArticlesMergeListener constructor.
     */
    public function __construct(ArticleRepositoryContract $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }

    public function handle(UserMergeEvent $event)
    {
        $mainUser = $event->getMainUser();
        $mergeUser = $event->getMergeUser();
        $mergeOptions = $event->getMergeOptions();

        if ($mergeOptions['created_articles'] ?? false) {
            foreach ($mergeUser->createdArticles as $article) {
                $this->articleRepository->update($article, [
                    'created_by_id' => $mainUser->id,
                ]);
            }
        }
    }
}
