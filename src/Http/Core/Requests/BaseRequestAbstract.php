<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class BaseRequestAbstract
 */
abstract class BaseRequestAbstract extends FormRequest
{
    /**
     * All expands that are allowed for this request
     */
    abstract public function allowedExpands(): array;

    /**
     * Authorizes that any attached expands are authorized for this request
     *
     * @throws AuthorizationException
     */
    public function authorizeExpands()
    {
        $expand = $this->query('expand');
        if (is_array($expand)) {
            foreach ($expand as $relation => $value) {
                if (! in_array($relation, $this->allowedExpands())) {
                    throw new AuthorizationException('The relation '.$relation.' cannot be expanded on this request.');
                }
            }
        }
    }

    /**
     * Whether or not the current user is authenticated to run this request
     */
    abstract public function authorize(): bool;
}
