<?php

declare(strict_types=1);

namespace App\Http\V1\Controllers\Messaging;

use Polis\Http\Core\Controllers\Messaging\EmailTemplateControllerAbstract;

/**
 * Class EmailTemplateController
 *
 * Concrete consumer wiring for the package's abstract email-template admin
 * controller, mirroring how App\Http\V1\Controllers\StatisticController wires
 * the StatisticControllerAbstract into the dummy app.
 */
class EmailTemplateController extends EmailTemplateControllerAbstract {}
