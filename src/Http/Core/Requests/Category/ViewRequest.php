<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Category;

use Polis\Http\Core\Requests\BaseUnauthenticatedRequest;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class ViewRequest
 */
class ViewRequest extends BaseUnauthenticatedRequest
{
    use HasNoExpands, HasNoRules;
}
