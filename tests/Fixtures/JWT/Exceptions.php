<?php

declare(strict_types=1);

/*
 * Fixture stub for PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException.
 *
 * The php-open-source-saver/jwt-auth package is not in this package's
 * composer.json — it's a consumer-provided dep. AuthenticationControllerAbstract
 * throws JWTException on bad credentials; tests need the FQCN to exist
 * to assertThrown() it.
 */

namespace PHPOpenSourceSaver\JWTAuth\Exceptions;

if (! class_exists(JWTException::class, false)) {
    class JWTException extends \RuntimeException {}
}
