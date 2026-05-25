<?php

declare(strict_types=1);

namespace Polis\Tests;

use Mockery\MockInterface;

interface CustomMockInterface extends MockInterface
{
    /**
     * @param  array  ...$function
     * @return mixed
     */
    public function shouldReceive(...$function);
}
