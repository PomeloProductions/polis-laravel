<?php

declare(strict_types=1);

namespace Polis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Polis\Traits\HasExternalSources;

/**
 * Polymorphic external-source pointer.
 *
 * Maps any model (via `item_type` + `item_id`) to an identifier in some
 * external system (`source` + `foreign_id`). One model may carry MULTIPLE
 * `foreign_id`s for the same `source` — for example a single CardPrinting
 * can be linked to several PriceCharting product IDs when PC tracks finer
 * variants than we model separately.
 *
 * Owning models opt in via {@see HasExternalSources}, which
 * provides the {@see HasExternalSources::sources()} morphMany
 * relationship plus convenience helpers for read / upsert / forget.
 *
 * Reverse lookup (when you have an external id and want the local model):
 *
 *     $row = Source::query()
 *         ->where('source', 'price_charting')
 *         ->where('foreign_id', '3457650')
 *         ->first();
 *     $model = $row?->item;  // the morphTo target
 *
 * @property int $id
 * @property string $item_type
 * @property int $item_id
 * @property string $source
 * @property string $foreign_id
 * @property string|null $url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Model $item
 */
class Source extends BaseModelAbstract
{
    /**
     * Override $table because the auto-derived name would be `sources` only
     * by coincidence (Laravel pluralisation of the FQCN's last segment).
     * Pinning it explicitly avoids surprises if the class is ever renamed.
     *
     * @var string
     */
    protected $table = 'sources';

    /**
     * Although BaseModelAbstract sets `$guarded = []` (intentional, see its
     * docblock), we list the writeable attributes here for static analysis
     * and IDE assistance. The trait's `updateOrCreate` call passes only
     * these keys.
     *
     * @var string[]
     */
    protected $fillable = [
        'item_type',
        'item_id',
        'source',
        'foreign_id',
        'url',
    ];

    /**
     * The owning model on the other side of the polymorphic pointer.
     */
    public function item(): MorphTo
    {
        return $this->morphTo();
    }
}
