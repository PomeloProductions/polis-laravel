<?php

declare(strict_types=1);

namespace Polis\Models;

use AdminUI\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class BaseModelAbstract
 */
abstract class BaseModelAbstract extends Model
{
    use EloquentJoin, HasFactory;

    /**
     * The deleted at field is by default hidden
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Mass assignment is intentionally unguarded on every Polis model.
     *
     * Polis relies on a layered defence — FormRequest validation, then
     * policy gates, then repository `$forcedValues` overrides — rather
     * than per-model `$fillable` lists. See the "Security model" section
     * of the package README before instantiating a model with raw input.
     *
     * Code paths that bypass the request layer (console commands,
     * listeners, seeders) MUST set attributes individually or pass an
     * explicit `$forcedValues` array through the repository instead of
     * forwarding client-supplied data here.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:c',
        'updated_at' => 'datetime:c',
        'deleted_at' => 'datetime:c',
    ];

    /**
     * All our models will be set with a deleted at timestamp
     */
    use SoftDeletes;
}
