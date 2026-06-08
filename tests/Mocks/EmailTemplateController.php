<?php

declare(strict_types=1);

namespace Polis\Tests\Mocks;

use Polis\Http\Core\Controllers\Messaging\EmailTemplateControllerAbstract;

/**
 * Concrete test subclass of the abstract controller — pure passthrough.
 * Lets the test suite construct a controller without needing a consumer
 * App\* concrete class.
 */
class EmailTemplateController extends EmailTemplateControllerAbstract {}
