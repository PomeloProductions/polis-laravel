<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Wiki;

/**
 * Interface ArticleVersionCalculationServiceContract
 */
interface ArticleVersionCalculationServiceContract
{
    /**
     * Figures out whether or not the new version is a major version
     */
    public function determineIfMajor(string $new, string $old): bool;

    /**
     * Figures out whether or not the new version is a minor version
     */
    public function determineIfMinor(string $new, string $old): bool;
}
