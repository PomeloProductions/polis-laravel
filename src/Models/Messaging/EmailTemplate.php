<?php

declare(strict_types=1);

namespace Polis\Models\Messaging;

use App\Models\Organization\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polis\Contracts\Messaging\EmailTemplateContract;
use Polis\Models\Wiki\Article;

/**
 * Class EmailTemplate
 *
 * Article-backed runtime-editable email template. Stored in the `articles`
 * table with the additive `key` and `organization_id` columns (see migration
 * 2026_05_30_000001_add_key_and_organization_id_to_articles_table.php).
 *
 * Hybrid storage decision: reusing Article infrastructure gives us versioning
 * (`articleVersions`), iteration history (`articleIterations`), and the
 * existing modification pipeline for free. The `key` column is what lets the
 * EmailTemplateRepository look up a template by a stable identifier
 * (e.g. "welcome", "renewal_reminder") instead of a numeric article id.
 *
 * Multi-tenant overrides: `organization_id` may be null (global default for
 * all tenants) or set to a specific organization (org-scoped override).
 * Lookup order is handled by EmailTemplateRepository::findByKey().
 *
 * The body of the email is stored in the article's content/iterations
 * pipeline; the subject lives in the `subject` accessor below which reads
 * from the article title for v0.2 simplicity. A future migration can split
 * subject + body into dedicated columns if that proves limiting.
 *
 * @property int $id
 * @property string $title
 * @property string|null $key
 * @property int|null $organization_id
 * @property-read string|null $content
 * @property-read string|null $subject
 * @property-read string|null $body_html
 */
class EmailTemplate extends Article implements EmailTemplateContract
{
    /**
     * Use the articles table directly — this is a thin specialization, not a
     * separate Eloquent table.
     */
    protected $table = 'articles';

    /**
     * Add the email-template-specific columns to fillable so they can be
     * mass-assigned via the repository.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'key',
        'organization_id',
        'created_by_id',
    ];

    /**
     * Register a global scope filtering articles to only those that have a
     * non-null `key` set — i.e. ones that were created as templates. Other
     * articles in the table (wiki entries, etc.) will not be returned.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('email_template', function (Builder $builder) {
            $builder->whereNotNull('articles.key');
        });
    }

    /**
     * Organization that owns this override (null = global default).
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Subject line for the rendered email. For v0.2 we use the article title.
     */
    public function getSubjectAttribute(): ?string
    {
        return $this->attributes['title'] ?? null;
    }

    /**
     * Body HTML for the rendered email. Reuses the existing Article content
     * pipeline — pulls from the current version's iteration content.
     */
    public function getBodyHtmlAttribute(): ?string
    {
        return $this->content;
    }

    public function morphRelationName(): string
    {
        return 'email_template';
    }

    /**
     * {@inheritDoc}
     */
    public function getSubject(): ?string
    {
        return $this->getSubjectAttribute();
    }

    /**
     * {@inheritDoc}
     */
    public function getBodyHtml(): ?string
    {
        return $this->getBodyHtmlAttribute();
    }
}
