<?php

namespace Silber\Bouncer\Database\Queries;

use Silber\Bouncer\Helpers;
use Silber\Bouncer\Database\Models;

class Roles
{
    /**
     * Constrain the given query by the provided role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  ...$roles
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function constrainWhereIs($query, ...$roles)
    {
        return $query->whereHas('roles', function ($query) use ($roles) {
            $query->whereIn('name', $roles);
        });
    }

    /**
     * Constrain the given query by all provided roles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  ...$roles
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function constrainWhereIsAll($query, ...$roles)
    {
        return $query->whereHas('roles', function ($query) use ($roles) {
            $query->whereIn('name', $roles);
        }, '=', count($roles));
    }

    /**
     * Constrain the given query by the provided role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  ...$roles
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function constrainWhereIsNot($query, ...$roles)
    {
        return $query->whereDoesntHave('roles', function ($query) use ($roles) {
            $query->whereIn('name', $roles);
        });
    }

    /**
     * Constrain the given roles query to those that were assigned to the given authorities.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection  $model
     * @param  array  $keys
     * @return void
     */
    public function constrainWhereAssignedTo($query, $model, array $keys = null)
    {
        list($model, $keys) = Helpers::extractModelAndKeys($model, $keys);

        $query->whereExists(function ($query) use ($model, $keys) {
            $table  = $model->getTable();
            $key    = "{$table}.{$model->getKeyName()}";
            $pivot  = Models::table('assigned_roles');
            $roles  = Models::table('roles');

            $query->from($table)
                  ->join($pivot, $key, '=', $pivot.'.entity_id')
                  ->whereColumn("{$pivot}.role_id", "{$roles}.id")
                  ->where("{$pivot}.entity_type", $model->getMorphClass())
                  ->whereIn($key, $keys);

            Models::scope()->applyToModelQuery($query, $roles);
            Models::scope()->applyToRelationQuery($query, $pivot);
        });
    }
}
