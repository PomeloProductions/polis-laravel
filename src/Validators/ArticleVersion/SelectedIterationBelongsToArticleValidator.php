<?php

declare(strict_types=1);

namespace Polis\Validators\ArticleVersion;

use App\Models\Wiki\ArticleIteration;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Polis\Contracts\Repositories\Wiki\ArticleIterationRepositoryContract;
use Polis\Validators\BaseValidatorAbstract;

/**
 * Class SelectedIterationBelongsToArticleValidator
 */
class SelectedIterationBelongsToArticleValidator extends BaseValidatorAbstract
{
    /**
     * @var string
     */
    const KEY = 'selected_iteration_belongs_to_article';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ArticleIterationRepositoryContract
     */
    private $iterationRepository;

    /**
     * SelectedIterationBelongsToArticleValidator constructor.
     */
    public function __construct(Request $request, ArticleIterationRepositoryContract $iterationRepository)
    {
        $this->request = $request;
        $this->iterationRepository = $iterationRepository;
    }

    /**
     * This is invoked by the validator rule 'selected_iteration_belongs_to_article'
     *
     * @param  $attribute  string the attribute name that is validating
     * @param  $value  mixed the value that we're testing
     * @param  $parameters  array
     * @param  $validator  Validator The Validator instance
     * @return bool
     */
    public function validate($attribute, $value, $parameters = [], ?Validator $validator = null)
    {
        $this->ensureValidatorAttribute('article_iteration_id', $attribute);

        if (! $value) {
            return true;
        }

        $article = $this->request->route('article', null);

        if (! $article) {
            return false;
        }

        try {
            /** @var ArticleIteration $iteration */
            $iteration = $this->iterationRepository->findOrFail($value);

            return $iteration->article_id == $article->id;

        } catch (ModelNotFoundException $e) {
        }

        return false;
    }
}
