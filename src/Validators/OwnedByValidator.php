<?php

declare(strict_types=1);

namespace Polis\Validators;

use Illuminate\Http\Request;
use Illuminate\Validation\Validator;

/**
 * Class OwnedByValidator
 */
class OwnedByValidator
{
    /**
     * The key for easy reference around the app
     */
    const KEY = 'owned_by';

    /**
     * @var Request
     */
    protected $request;

    /**
     * OwnedByValidator constructor.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * This is invoked by the validator rule 'owned_by'
     *
     * @param  array  $parameters
     * @return bool
     */
    public function validate($attribute, $value, $parameters = [], ?Validator $validator = null)
    {
        $ownerRequestParamName = array_shift($parameters);

        $relatedObject = $this->request->route($ownerRequestParamName);

        while (count($parameters)) {
            $relation = array_shift($parameters);
            $relatedObject = $relatedObject->{$relation};
        }

        return $relatedObject->contains('id', $value);
    }
}
