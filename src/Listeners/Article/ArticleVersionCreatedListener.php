<?php

declare(strict_types=1);

namespace Polis\Listeners\Article;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Polis\Contracts\Repositories\Wiki\ArticleVersionRepositoryContract;
use Polis\Contracts\Services\Wiki\ArticleVersionCalculationServiceContract;
use Polis\Events\Article\ArticleVersionCreatedEvent;

/**
 * Class ArticleVersionCreatedListener
 */
class ArticleVersionCreatedListener implements ShouldQueue
{
    use Queueable;

    /**
     * @var ArticleVersionRepositoryContract
     */
    private $repository;

    /**
     * @var ArticleVersionCalculationServiceContract
     */
    private $calculationService;

    /**
     * ArticleVersionCreatedListener constructor.
     */
    public function __construct(ArticleVersionRepositoryContract $repository,
        ArticleVersionCalculationServiceContract $calculationService)
    {
        $this->repository = $repository;
        $this->calculationService = $calculationService;
    }

    public function handle(ArticleVersionCreatedEvent $event)
    {
        $newVersion = $event->getNewVersion();
        $oldVersion = $event->getOldVersion();

        $major = 1;
        $minor = 0;
        $patch = 0;

        if ($oldVersion && $oldVersion->name) {

            $oldVersionNumber = explode('.', $oldVersion->name);

            if (count($oldVersionNumber) >= 3) {

                $newVersionContent = $newVersion->articleIteration ? $newVersion->articleIteration->content : null;
                $oldVersionContent = $oldVersion->articleIteration ? $oldVersion->articleIteration->content : null;

                $major = $oldVersionNumber[0] - 0;
                $minor = $oldVersionNumber[1] - 0;
                $patch = $oldVersionNumber[2] - 0;

                switch (true) {
                    case $newVersionContent == null || $oldVersionContent == null:
                    case $this->calculationService->determineIfMajor($newVersionContent, $oldVersionContent):
                        $major++;
                        $minor = 0;
                        $patch = 0;
                        break;

                    case $this->calculationService->determineIfMinor($newVersionContent, $oldVersionContent):
                        $minor++;
                        $patch = 0;
                        break;

                    default:
                        $patch++;
                        break;
                }
            }
        }

        $this->repository->update($newVersion, [
            'name' => $major.'.'.$minor.'.'.$patch,
        ]);
    }
}
