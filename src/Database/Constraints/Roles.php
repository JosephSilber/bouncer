<?php

namespace Silber\Bouncer\Database\Constraints;

class Roles
{
    /**
     * Constrain the given users query by the provided role.
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
     * Constrain the given users query by all provided roles.
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
}
