<?php

namespace Silber\Bouncer\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface Scope
{
    /**
     * Append the tenant ID to the given cache key.
     *
     * @param  string  $key
     * @return string
     */
    public function appendToCacheKey($key);

    /**
     * Scope the given model to the current tenant.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function applyToModel(Model $model);

    /**
     * Scope the given model query to the current tenant.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $table
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public function applyToModelQuery($query, $table);

    /**
     * Scope the given relationship query to the current tenant.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $table
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public function applyToRelationQuery($query, $table);

    /**
     * Scope the given relation to the current tenant.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsToMany  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function applyToRelation(BelongsToMany $relation);

    /**
     * Get the additional attributes for pivot table records.
     *
     * @param  string|null  $authority
     * @return array
     */
    public function getAttachAttributes($authority = null);
}
