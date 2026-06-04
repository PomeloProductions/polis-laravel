<?php

declare(strict_types=1);

namespace Polis\Models\Messaging;

use App\Models\Organization\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polis\Contracts\Messaging\PushTemplateContract;
use Polis\Models\Wiki\Article;

/**
 * Class PushTemplate
 *
 * Article-backed runtime-editable push notification template. Mirrors
 * Polis\Models\Messaging\EmailTemplate one-to-one: stored in the
 * `articles` table, scoped to rows that have the additive `key` column
 * set (see migration 2026_05_30_000001_add_key_and_organization_id_to_articles_table.php).
 *
 * Hybrid storage decision: reusing Article infrastructure gives us
 * versioning (`articleVersions`), iteration history (`articleIterations`),
 * and the existing modification pipeline for free — exactly as
 * EmailTemplate does. The `key` column is what lets the
 * PushTemplateRepository look up a template by a stable identifier (e.g.
 * "contact_created") instead of a numeric article id.
 *
 * Multi-tenant overrides: `organization_id` may be null (global default
 * for all tenants) or set to a specific organization (org-scoped
 * override). Lookup order is handled by PushTemplateRepository::findByKey.
 *
 * Push notifications carry a `title` and a plain-text `body` (not HTML).
 * The morphRelationName below ('push_template') is what disambiguates a
 * push template from an email template at the resource-indexing layer
 * (see Polis\Observers\IndexableModelObserver). Application code is
 * expected to keep push template keys distinct from email template keys
 * since both share the same `articles.key` column.
 *
 * @property int $id
 * @property string $title
 * @property string|null $key
 * @property int|null $organization_id
 * @property-read string|null $content
 * @property-read string|null $body
 */
class PushTemplate extends Article implements PushTemplateContract
{
    /**
     * Use the articles table directly — this is a thin specialization,
     * not a separate Eloquent table.
     */
    protected $table = 'articles';

    /**
     * Add the push-template-specific columns to fillable so they can be
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
     * Register a global scope filtering articles to only those that have
     * a non-null `key` set — i.e. ones that were created as templates.
     * Other articles in the table (wiki entries, etc.) will not be
     * returned. Mirrors EmailTemplate's global scope.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('push_template', function (Builder $builder) {
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
     * Title for the rendered push notification. Reuses the article's
     * existing `title` column.
     */
    public function getTitleAttribute(): ?string
    {
        return $this->attributes['title'] ?? null;
    }

    /**
     * Plain-text body for the rendered push notification. Reuses the
     * existing Article content pipeline — pulls from the current
     * version's iteration content. (Push bodies are plain text, but
     * since this content is stored in the same place as email body HTML,
     * runtime authors should write plain text only; the
     * PushTemplateRenderingService does not sanitize HTML the way the
     * email service does.)
     */
    public function getBodyAttribute(): ?string
    {
        return $this->content;
    }

    public function morphRelationName(): string
    {
        return 'push_template';
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle(): ?string
    {
        return $this->getTitleAttribute();
    }

    /**
     * {@inheritDoc}
     */
    public function getBody(): ?string
    {
        return $this->getBodyAttribute();
    }
}
