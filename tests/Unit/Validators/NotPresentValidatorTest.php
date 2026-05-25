<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Validators;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Validation\Validator;
use Polis\Tests\TestCase;
use Polis\Validators\NotPresentValidator;

/**
 * Class NotPresentValidatorTest
 */
final class NotPresentValidatorTest extends TestCase
{
    public function test_not_present_true(): void
    {
        $validator = new NotPresentValidator;

        $translatorMock = mock(Translator::class);
        $validatorObject = new Validator($translatorMock, ['item_here' => 1], []);

        $this->assertTrue($validator->validate('not_here', null, [], $validatorObject));
    }

    public function test_not_present_false(): void
    {
        $validator = new NotPresentValidator;

        $translatorMock = mock(Translator::class);
        $validatorObject = new Validator($translatorMock, ['item_here_value' => 1, 'item_here_null' => null], []);

        $this->assertFalse($validator->validate('item_here_value', 1, [], $validatorObject));
        $this->assertFalse($validator->validate('item_here_null', null, [], $validatorObject));
    }
}
