<?php

declare(strict_types=1);

namespace App\Http\V1\Controllers\User;

use Polis\Http\Core\Controllers\User\TodoControllerAbstract;

/**
 * Class TodoController
 *
 * Concrete consumer wiring for the package's abstract Todo controller. The
 * whole Todo subsystem (calendar/day/week/month/year pages, task-node trees,
 * balances, timers, templates and settings) is ported verbatim from PolisOS
 * into the package; this thin subclass is all a consumer needs to route it,
 * mirroring App\Http\V1\Controllers\StatisticController.
 */
class TodoController extends TodoControllerAbstract {}
