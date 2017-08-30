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
     * @param  string  $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function constrainWhereIs($query, $role)
    {
        $roles = array_slice(func_get_args(), 1);

        return $query->whereHas('roles', function ($query) use ($roles) {
            $query->whereIn('name', $roles);
        });
    }

    /**
     * Constrain the given query by all provided roles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function constrainWhereIsAll($query, $role)
    {
        $roles = array_slice(func_get_args(), 1);

        return $query->whereHas('roles', function ($query) use ($roles) {
            $query->whereIn('name', $roles);
        }, '=', count($roles));
    }

    /**
     * Constrain the given query by the provided role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function constrainWhereIsNot($query, $role)
    {
        $roles = array_slice(func_get_args(), 1);

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
            $prefix = Models::prefix();

            $query->from($table)
                  ->join($pivot, $key, '=', $pivot.'.entity_id')
                  ->whereRaw("{$prefix}{$pivot}.role_id = {$prefix}{$roles}.id")
                  ->where("{$pivot}.entity_type", $model->getMorphClass())
                  ->whereIn($key, $keys);
        });
    }
}
