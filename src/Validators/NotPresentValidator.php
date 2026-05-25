<?php

declare(strict_types=1);

namespace Polis\Validators;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;

/**
 * Class AnyBooleanValueValidator
 */
class NotPresentValidator
{
    /**
     * This is invoked by the validator rule 'not_present'
     *
     * @param  $attribute  string the attribute name that is validating
     * @param  $value  mixed the value that we're testing
     * @param  $parameters  array
     * @param  $validator  Validator The Validator instance
     * @return bool
     */
    public function validate($attribute, $value, $parameters = [], ?Validator $validator = null)
    {
        return ! Arr::has($validator->attributes(), $attribute);
    }
}
