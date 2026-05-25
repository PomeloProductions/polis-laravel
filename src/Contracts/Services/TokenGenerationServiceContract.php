<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

/**
 * Interface TokenGenerationServiceContract
 */
interface TokenGenerationServiceContract
{
    /**
     * Generates a token
     *
     * @param  int  $length
     */
    public function generateToken($length = 40): string;
}
