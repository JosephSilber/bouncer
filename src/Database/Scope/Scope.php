<?php

namespace Silber\Bouncer\Database\Scope;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Scope
{
    /**
     * The tenant ID by which to scope all queries.
     *
     * @var mixed
     */
    protected $scope = null;

    /**
     * Determines whether the scope is only applied to relationships.
     *
     * Set to true if you don't want the scopes on the role/ability models.
     *
     * @var mixed
     */
    protected $onlyScopeRelations = false;

    /**
     * Scope all queries to the given tenant ID.
     *
     * @param  mixed  $id
     * @return void
     */
    public function scopeTo($id)
    {
        $this->scope = $id;

        $this->onlyScopeRelations = false;
    }

    /**
     * Scope only the relationships to the given tenant ID.
     *
     * @param  mixed  $id
     * @return void
     */
    public function scopeRelationsTo($id)
    {
        $this->scope = $id;

        $this->onlyScopeRelations = true;
    }

    /**
     * Do not scope any queries.
     *
     * @return void
     */
    public function reset()
    {
        $this->scope = null;

        $this->onlyScopeRelations = false;
    }

    /**
     * Scope the given model to the current tenant.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function applyToModel(Model $model)
    {
        if (! $this->onlyScopeRelations && ! is_null($this->scope)) {
            $model->scope = $this->scope;
        }

        return $model;
    }

    /**
     * Scope the given model query to the current tenant.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $table
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public function applyToModelQuery($query, $table)
    {
        if (! is_null($this->scope) && ! $this->onlyScopeRelations) {
            $query->where("{$table}.scope", $this->scope);
        }

        return $query;
    }

    /**
     * Scope the given relationship query to the current tenant.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $table
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public function applyToRelationQuery($query, $table)
    {
        if (! is_null($this->scope)) {
            $query->where("{$table}.scope", $this->scope);
        }

        return $query;
    }

    /**
     * Scope the given relation to the current tenant.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsToMany  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function applyToRelation(BelongsToMany $relation)
    {
        if (! is_null($this->scope)) {
            $relation->wherePivot('scope', $this->scope);
        }

        return $relation;
    }

    /**
     * Get the additional attributes for pivot table records.
     *
     * @return array
     */
    public function getAttachAttributes()
    {
        if (is_null($this->scope)) {
            return [];
        }

        return ['scope' => $this->scope];
    }
}
