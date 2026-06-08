<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Requests;

use Polis\Http\Core\Requests\BaseRequestAbstract;

/**
 * Generic stub used in place of every consumer-side
 * App\Http\Core\Requests\* class referenced by the abstract controllers.
 *
 * Why one stub aliased many ways?
 *
 * The abstract controllers in src/Http/Core/Controllers/* type-hint
 * concrete consumer-app FormRequest subclasses (e.g. App\Http\Core\Requests\Category\IndexRequest)
 * on their action methods. Those classes don't exist in this package, so
 * we can't construct or mock them through their real definitions. PHP's
 * type system *does* honor class_alias though — aliasing this single
 * stub under each expected FQCN makes Mockery::mock('App\\Http\\Core\\Requests\\Category\\IndexRequest')
 * proxy a real concrete class and satisfy the controller's parameter type.
 *
 * The stub itself extends BaseRequestAbstract so trait helpers in
 * HasIndexRequests / HasViewRequests (which type-hint BaseRequestAbstract)
 * also accept the same mock.
 */
class StubRequest extends BaseRequestAbstract
{
    public function authorize(): bool
    {
        return true;
    }

    public function allowedExpands(): array
    {
        return [];
    }

    public function rules(): array
    {
        return [];
    }
}
