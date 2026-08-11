<?php

declare(strict_types=1);

namespace App\Http\V1\Controllers\Organization;

use Polis\Http\Core\Controllers\Organization\OrganizationArticleControllerAbstract;

/**
 * Concrete org-scoped Article ("contract") controller for the dummy app.
 * Serves GET /organizations/{organization}/articles.
 */
class ArticleController extends OrganizationArticleControllerAbstract {}
