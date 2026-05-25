<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

/**
 * Class BaseControllerAbstract
 *
 * @SWG\Swagger(
 *     schemes={"https"},
 *     host="dev-api.projectathenia.com",
 *     basePath="/v1",
 *     produces={"application/json"},
 *     consumes={"application/json"},
 *
 *     @SWG\Info(
 *         version="v1",
 *         title="Project Athenia API"
 *     )
 * )
 *
 * All documentation tags are detailed below...
 *
 * @SWG\Tag(
 *     name="Auth",
 *     description="Any information about how to authenticate with the API"
 * )
 * @SWG\Tag(
 *     name="Misc",
 *     description="Any general routes in the system."
 * )
 * @SWG\Tag(
 *     name="Assets",
 *     description="Any routes related to assets."
 * )
 * @SWG\Tag(
 *     name="Backgrounds",
 *     description="Any routes related to background asset models."
 * )
 * @SWG\Tag(
 *     name="Foregrounds",
 *     description="Any routes related to foreground asset models."
 * )
 * @SWG\Tag(
 *     name="Users",
 *     description="Any information that is related to the user object of the app"
 * )
 * @SWG\Tag(
 *     name="Roles",
 *     description="Any information that is related to the user roles of the App"
 * )
 * @SWG\Tag(
 *     name="LanguageContents",
 *     description="Any information that relates to the make up of the language itself"
 * )
 * @SWG\Tag(
 *     name="Words",
 *     description="Any information related to a word of the language"
 * )
 * @SWG\Tag(
 *     name="Characters",
 *     description="Any information related to a character of the language"
 * )
 * @SWG\Tag(
 *     name="Radicals",
 *     description="Any information related to a radical of the language"
 * )
 *
 * Http parameters defined below...
 *
 * @SWG\Parameter(
 *      parameter="AuthorizationHeader",
 *      name="Authorization",
 *      in="header",
 *      required=true,
 *      type="string",
 *      format="Bearer"
 * )
 * @SWG\Parameter(
 *      parameter="PaginationPage",
 *      name="page",
 *      in="query",
 *      description="Page number",
 *      type="integer",
 *      format="int32",
 *      minimum=1,
 *      required=false,
 *      default=1
 * )
 * @SWG\Parameter(
 *     parameter="PaginationLimit",
 *     name="limit",
 *     in="query",
 *     description="Number of elements per page",
 *     type="integer",
 *     format="int32",
 *     minimum=1,
 *     maximum=100,
 *     required=false,
 *     default=10
 * )
 * @SWG\Parameter(
 *     parameter="FilterParameter",
 *     name="filter",
 *     in="query",
 *     description="Exact matches on model properties and a value, key is property, value is exact match value.",
 *     type="array",
 *     required=false,
 *
 *     @SWG\Items(
 *          type="string"
 *     )
 * )
 *
 * @SWG\Parameter(
 *     parameter="SearchParameter",
 *     name="search",
 *     in="query",
 *     description="Executes a search based on the format specified in documentation, key is property, value is the format for the search.",
 *     type="array",
 *     required=false,
 *
 *     @SWG\Items(
 *          type="string"
 *     )
 * )
 *
 * @SWG\Parameter(
 *     parameter="ExpandParameter",
 *     name="expand",
 *     in="query",
 *     description="Expands a field based on the format specified in documentation, key is property and value is the columns.",
 *     type="array",
 *     required=false,
 *
 *     @SWG\Items(
 *          type="string"
 *     )
 * )
 *
 * Http status code definitions...
 *
 * @SWG\Response(
 *     response="Standard400BadRequestResponse",
 *     description="Bad request, validation error",
 *
 *     @SWG\Schema(ref="#/definitions/Error"),
 *
 *     @SWG\Header(
 *         header="X-RateLimit-Limit",
 *         description="The number of allowed requests in the period",
 *         type="integer"
 *     ),
 *     @SWG\Header(
 *         header="X-RateLimit-Remaining",
 *         description="The number of remaining requests in the period",
 *         type="integer"
 *     )
 * )
 *
 * @SWG\Response(
 *     response="Standard401UnauthorizedResponse",
 *     description="Bad request, the users authentication is invalid, or they user does not have access to the requested resource.",
 *
 *     @SWG\Schema(ref="#/definitions/Error"),
 *
 *     @SWG\Header(
 *         header="X-RateLimit-Limit",
 *         description="The number of allowed requests in the period",
 *         type="integer"
 *     ),
 *     @SWG\Header(
 *         header="X-RateLimit-Remaining",
 *         description="The number of remaining requests in the period",
 *         type="integer"
 *     )
 * )
 *
 * @SWG\Response(
 *      response="Standard404ItemNotFoundResponse",
 *      description="Unauthorized",
 *
 *      @SWG\Schema(ref="#/definitions/Error"),
 *
 *      @SWG\Header(
 *          header="X-RateLimit-Limit",
 *          description="The number of allowed requests in the period",
 *          type="integer"
 *      ),
 *      @SWG\Header(
 *          header="X-RateLimit-Remaining",
 *          description="The number of remaining requests in the period",
 *          type="integer"
 *      )
 * )
 *
 * @SWG\Response(
 *     response="Standard404PagingRequestTooLarge",
 *     description="Pagination request is larger than the pages available",
 *
 *     @SWG\Schema(ref="#/definitions/Paging"),
 *
 *     @SWG\Header(
 *         header="X-RateLimit-Limit",
 *         description="The number of allowed requests in the period",
 *         type="integer"
 *     ),
 *     @SWG\Header(
 *         header="X-RateLimit-Remaining",
 *         description="The number of remaining requests in the period",
 *         type="integer"
 *     )
 * )
 *
 * @SWG\Response(
 *      response="Standard500ErrorResponse",
 *      description="Unexpected error",
 *
 *      @SWG\Schema(ref="#/definitions/Error"),
 *
 *      @SWG\Header(
 *          header="X-RateLimit-Limit",
 *          description="The number of allowed requests in the period",
 *          type="integer"
 *      ),
 *      @SWG\Header(
 *          header="X-RateLimit-Remaining",
 *          description="The number of remaining requests in the period",
 *          type="integer"
 *      )
 * )
 *
 * General System wide response variables below...
 *
 * @SWG\Definition(
 *     definition="Error",
 *     type="object",
 *     required={"message"},
 *
 *     @SWG\Property(
 *          property="message",
 *          type="string",
 *          description="The human-readable error message"
 *      ),
 *     @SWG\Property(
 *          property="errors",
 *          type="object",
 *          description="Contains a key for the error field, and an array of string error messages for the field."
 *     )
 * )
 *
 * @SWG\Definition(
 *     definition="Status",
 *     type="object",
 *     required={"status"},
 *
 *     @SWG\Property(
 *         property="status",
 *         type="string",
 *         description="The status of the request. Probably OK."
 *     )
 * )
 *
 * @SWG\Definition(
 *     definition="Paging",
 *
 *     @SWG\Property(
 *          property="total",
 *          type="integer",
 *          format="int32",
 *          description="The total elements in this collection",
 *          minimum=0
 *     ),
 *     @SWG\Property(
 *          property="per_page",
 *          type="integer",
 *          format="int32",
 *          minimum=1,
 *          maximum=100,
 *          description="The amount of elements per page"
 *      ),
 *     @SWG\Property(
 *          property="current_page",
 *          type="integer",
 *          format="int32",
 *          minimum=1,
 *          description="The current page number viewing"
 *      ),
 *     @SWG\Property(
 *          property="last_page",
 *          type="integer",
 *          format="int32",
 *          description="The last page number of results"
 *      ),
 *     @SWG\Property(
 *          property="next_page_url",
 *          type="string",
 *          format="url",
 *          description="The url of the next page results"
 *      ),
 *     @SWG\Property(
 *          property="prev_page_url",
 *          type="string",
 *          format="url",
 *          description="The url of the previous page of results"
 *      ),
 *     @SWG\Property(
 *          property="from",
 *          type="integer",
 *          format="int32",
 *          description="The beginning of the range of items"
 *      ),
 *     @SWG\Property(
 *          property="to",
 *          type="integer",
 *          format="int32",
 *          description="The end of the range of items"
 *      )
 * )
 */
abstract class BaseControllerAbstract extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
