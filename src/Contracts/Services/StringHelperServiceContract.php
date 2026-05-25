<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

/**
 * Interface StringHelperServiceContract
 */
interface StringHelperServiceContract
{
    /**
     * Handles a multibyte string replace
     *
     * @return mixed
     */
    public function mbSubstrReplace($original, $replacement, $position, $length);

    /**
     * Checks whether or not the passed in string contains a domain name within it
     */
    public function hasDomainName(string $needle): bool;
}
