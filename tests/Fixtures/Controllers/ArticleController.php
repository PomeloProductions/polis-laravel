<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Controllers;

use Polis\Http\Core\Controllers\ArticleControllerAbstract;

/**
 * Concrete fixture controller for ArticleControllerAbstract.
 *
 * Pure passthrough — exists so the package's test suite can instantiate
 * the controller without needing a consumer App\* concrete subclass.
 */
class ArticleController extends ArticleControllerAbstract {}
