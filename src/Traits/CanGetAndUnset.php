<?php

declare(strict_types=1);

namespace Polis\Traits;

/**
 * Trait CanGetAndUnset
 */
trait CanGetAndUnset
{
    /**
     * Retrieves the needle from the haystack, removes the needle from the haystack, and returns the result
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function getAndUnset(array &$haystack, string $needle, $default = null)
    {
        $value = $haystack[$needle] ?? $default;
        unset($haystack[$needle]);

        return $value;
    }
}
