<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Class StatusControllerAbstract
 */
abstract class StatusControllerAbstract extends BaseControllerAbstract
{
    /**
     * Get the Status Object
     *
     * @SWG\Get(
     *     path="/status",
     *     summary="Displays a status object indicating the status of all API services",
     *     tags={"Misc"},
     *
     *     @SWG\Response(
     *          response=200,
     *          description="200 Status is always sent, regardless of the actual status of the API from the API Status Object",
     *
     *          @SWG\Schema(ref="#/definitions/Status"),
     *
     *          @SWG\Header(
     *              header="X-RateLimit-Limit",
     *              description="The number of allowed requests in the period",
     *              type="integer"
     *          ),
     *          @SWG\Header(
     *              header="X-RateLimit-Remaining",
     *              description="The number of remaining requests in the period",
     *              type="integer"
     *          )
     *      ),
     *
     *     @SWG\Response(
     *          response="default",
     *          description="Unexpected error",
     *
     *          @SWG\Schema(ref="#/definitions/Error"),
     *
     *          @SWG\Header(
     *              header="X-RateLimit-Limit",
     *              description="The number of allowed requests in the period",
     *              type="integer"
     *          ),
     *          @SWG\Header(
     *              header="X-RateLimit-Remaining",
     *              description="The number of remaining requests in the period",
     *              type="integer"
     *          )
     *      ),
     * )
     *
     * @return JsonResponse
     */
    public function __invoke()
    {
        return new JsonResponse([
            'status' => 'ok',
        ]);
    }
}
