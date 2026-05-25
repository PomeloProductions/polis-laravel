<?php

declare(strict_types=1);

namespace Polis\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Polis\Exceptions\ValidationException;

/**
 * Class SearchFilterParsingMiddleware
 */
class SearchFilterParsingMiddleware
{
    private Repository $config;

    /**
     * SearchFilterParsingMiddleware constructor.
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Add a parsed filter and search to the request as the `refine` variable
     *
     * @param  Request  $request
     * @return mixed
     *
     * @throws ValidationException
     */
    public function handle($request, Closure $next)
    {
        $cleanedFilter = [];

        if ($filters = $request->query('filter')) {
            if (is_array($filters)) {
                foreach ($filters as $key => $value) {
                    $cleanedFilter = $this->processQueryEntry($cleanedFilter, $key, $value);
                }
            }
        }
        if ($cleanedFilter) {
            $request->query->set('cleaned_filter', $cleanedFilter);
        }

        $cleanedSearch = [];

        if ($search = $request->query('search')) {
            if (is_array($search)) {
                foreach ($search as $key => $searchTermContainer) {

                    if (is_array($searchTermContainer)) {

                        foreach ($searchTermContainer as $individualSearch) {
                            $cleanedSearch = $this->processQueryEntry($cleanedSearch, $key, $individualSearch);
                        }
                    } else {

                        $cleanedSearch = $this->processQueryEntry($cleanedSearch, $key, $searchTermContainer);
                    }
                }
            }
        }

        if ($cleanedSearch) {
            $request->query->set('cleaned_search', $cleanedSearch);
        }

        return $next($request);
    }

    /**
     * Creates a search term, and returns the array with the new search term added
     *
     * @throws ValidationException
     */
    private function processQueryEntry($currentQuery, $key, $value): array
    {
        $parts = explode(',', $value, 2);

        if (count($parts) == 1) {
            switch ($parts[0]) {
                case 'notnull':
                    $searchType = 'IS NOT NULL';
                    $searchTerm = null;
                    break;

                case 'null':
                    $searchType = 'IS NULL';
                    $searchTerm = null;
                    break;

                default:
                    $searchType = '=';
                    $searchTerm = urldecode($parts[0]);
                    break;
            }

            $currentQuery[] = [$key, $searchType, $searchTerm];
        } else {

            $searchTerm = $parts[1];

            switch ($parts[0]) {
                case 'gt':
                    $searchType = '>';
                    break;
                case 'gte':
                    $searchType = '>=';
                    break;
                case 'lt':
                    $searchType = '<';
                    break;
                case 'lte':
                    $searchType = '<=';
                    break;
                case 'eq':
                    $searchType = '=';
                    break;
                case 'ne':
                    $searchType = '!=';
                    break;

                case 'in':
                    $searchType = 'in';
                    $searchTerm = explode(',', $searchTerm);
                    break;
                case 'notin':
                    $searchType = 'not in';
                    $searchTerm = explode(',', $searchTerm);
                    break;

                case 'like':
                    $searchType = 'like';
                    if (Str::startsWith($searchTerm, '*')) {
                        $searchTerm = '%'.substr($searchTerm, 1);
                    }
                    if (Str::endsWith($searchTerm, '*')) {
                        $searchTerm = substr($searchTerm, 0, -1).'%';
                    }
                    break;

                case 'between':
                    $searchType = 'between';
                    $searchTerm = explode(',', $searchTerm);
                    break;

                default:
                    throw new ValidationException(sprintf('Search term [%s] is has an invalid qualifier [%s]', $key, $parts[0]));
            }

            if ($searchType == 'between') { // I wish I could figure out how to get the 'between' operator to work - but it has a hardcoded $type = 'basic' in the where()
                $currentQuery[] = [$key, '>=', urldecode($searchTerm[0])];
                $currentQuery[] = [$key, '<=', urldecode($searchTerm[1])];
            } elseif ($searchType == 'like') {
                $currentQuery[] = [
                    $key,
                    (Str::contains($this->config->get('database.default'), 'pgsql') ? 'i' : '').$searchType,
                    $searchTerm,
                ];
            } else {
                $currentQuery[] = [$key, $searchType, is_array($searchTerm) ? array_map('urldecode', $searchTerm) : urldecode($searchTerm)];
            }
        }

        return $currentQuery;
    }
}
