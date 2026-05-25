<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Feature;

use Polis\Http\Core\Requests\BaseUnauthenticatedRequest;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class IndexRequest
 */
class IndexRequest extends BaseUnauthenticatedRequest
{
    use HasNoExpands;
}
