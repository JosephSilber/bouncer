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
     * @param  string|null  $table
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public function applyToModelQuery($query, $table = null);

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
     * Get the current scope value.
     *
     * @return mixed
     */
    public function get();

    /**
     * Get the additional attributes for pivot table records.
     *
     * @param  string|null  $authority
     * @return array
     */
    public function getAttachAttributes($authority = null);

    /**
     * Run the given callback with the given temporary scope.
     *
     * @param  mixed   $scope
     * @param  callable  $callback
     * @return mixed
     */
    public function onceTo($scope, callable $callback);

    /**
     * Remove the scope completely.
     *
     * @return $this
     */
    public function remove();

    /**
     * Run the given callback without the scope applied.
     *
     * @param  callable  $callback
     * @return mixed
     */
    public function removeOnce(callable $callback);
}
