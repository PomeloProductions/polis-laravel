<?php

declare(strict_types=1);

/*
 * Validation message overrides for the dummy consumer app's test harness.
 *
 * The ported PolisOS Feature tests were written against Laravel's pre-11
 * validation phrasing (e.g. "The title must be a string." rather than Laravel
 * 11/12's "The title field must be a string."). Rather than rewrite hundreds of
 * assertions, restore the older phrasing here so the assertions match. Only the
 * messages the ported tests assert on are overridden; everything else falls
 * back to the framework defaults.
 */

return [
    'array' => 'The :attribute must be an array.',
    'email' => 'The :attribute must be a valid email address.',
    'integer' => 'The :attribute must be an integer.',
    'max' => [
        'array' => 'The :attribute may not have more than :max items.',
        'file' => 'The :attribute may not be greater than :max kilobytes.',
        'numeric' => 'The :attribute may not be greater than :max.',
        'string' => 'The :attribute may not be greater than :max characters.',
    ],
    'min' => [
        'array' => 'The :attribute must have at least :min items.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'numeric' => 'The :attribute must be at least :min.',
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'numeric' => 'The :attribute must be a number.',
    'string' => 'The :attribute must be a string.',
];
