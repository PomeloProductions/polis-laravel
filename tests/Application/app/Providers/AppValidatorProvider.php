<?php
declare(strict_types=1);

namespace App\Providers;

use Polis\Providers\BaseValidatorProvider;
use Illuminate\Contracts\Validation\Factory;

class AppValidatorProvider extends BaseValidatorProvider
{
    /**
     * Register any of your application specific validators here
     *
     * @param Factory $validatorFactory
     * @return void
     */
    public function registerValidators(Factory $validatorFactory): void
    {
    }
}