<?php

namespace Silber\Bouncer\Database\Scope;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Contracts\Scope as ScopeContract;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Scope implements ScopeContract
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
     * Determines whether roles' abilities should be scoped.
     *
     * @var mixed
     */
    protected $scopeRoleAbilities = true;

    /**
     * Scope queries to the given tenant ID.
     *
     * @param  mixed  $id
     * @return $this
     */
    public function to($id)
    {
        $this->scope = $id;

        return $this;
    }

    /**
     * Only scope relationships. Models should stay global.
     *
     * @param  bool  $boolean
     * @return $this
     */
    public function onlyRelations($boolean = true)
    {
        $this->onlyScopeRelations = $boolean;

        return $this;
    }

    /**
     * Don't scope abilities granted to roles.
     *
     * The role <=> ability associations will be global.
     *
     * @return $this
     */
    public function dontScopeRoleAbilities()
    {
        $this->scopeRoleAbilities = false;

        return $this;
    }

    /**
     * Append the tenant ID to the given cache key.
     *
     * @param  string  $key
     * @return string
     */
    public function appendToCacheKey($key)
    {
        return is_null($this->scope) ? $key : $key.'-'.$this->scope;
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
        if (is_null($this->scope) || $this->onlyScopeRelations) {
            return $query;
        }

        return $this->applyToQuery($query, $table);
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
        if (is_null($this->scope)) {
            return $query;
        }

        return $this->applyToQuery($query, $table);
    }

    /**
     * Scope the given relation to the current tenant.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsToMany  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function applyToRelation(BelongsToMany $relation)
    {
        $this->applyToRelationQuery(
            $relation->getQuery(),
            $relation->getTable()
        );

        return $relation;
    }

    /**
     * Apply the current scope to the given query.
     *
     * This internal method does not check whether
     * the given query needs to be scoped. That
     * is fully the caller's responsibility.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $table
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    protected function applyToQuery($query, $table)
    {
        return $query->where(function ($query) use ($table) {
            $query->where("{$table}.scope", $this->scope)
                  ->orWhereNull("{$table}.scope");
        });
    }

    /**
     * Get the additional attributes for pivot table records.
     *
     * @param  string|null  $authority
     * @return array
     */
    public function getAttachAttributes($authority = null)
    {
        if (is_null($this->scope)) {
            return [];
        }

        if (! $this->scopeRoleAbilities && $this->isRoleClass($authority))
        {
            return [];
        }

        return ['scope' => $this->scope];
    }

    /**
     * Determine whether the given class name is the role model.
     *
     * @param  string|null $className
     * @return bool
     */
    protected function isRoleClass($className)
    {
        return Models::classname(Role::class) === $className;
    }
}
