<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Validation\Factory;
use Polis\Providers\BaseValidatorProvider;

class AppValidatorProvider extends BaseValidatorProvider
{
    /**
     * Register any of your application specific validators here
     */
    public function registerValidators(Factory $validatorFactory): void {}
}
