<?php

declare(strict_types=1);

namespace Polis\Services;

use Illuminate\Support\Str;
use Polis\Contracts\Services\TokenGenerationServiceContract;

/**
 * Class TokenGenerationService
 */
class TokenGenerationService implements TokenGenerationServiceContract
{
    /**
     * Generates a token
     *
     * @param  int  $length
     */
    public function generateToken($length = 40): string
    {
        return Str::random($length);
    }
}
