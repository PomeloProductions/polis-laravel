<?php
declare(strict_types=1);

namespace App\Http\V1\Controllers;

use Polis\Http\Core\Controllers\AuthenticationControllerAbstract;

/**
 * Class AuthenticationController
 *
 * @SWG\Definition(
 *     definition="AuthenticationToken",
 *     type="object",
 *     @SWG\Property(
 *         property="token",
 *         type="string",
 *         description="The authentication token"
 *     )
 * )
 *
 * @package App\Http\V1\Controllers
 */
class AuthenticationController extends AuthenticationControllerAbstract
{}