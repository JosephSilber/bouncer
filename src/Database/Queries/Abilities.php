<?php

namespace Silber\Bouncer\Database\Queries;

use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;

class Abilities
{
    /**
     * Get a query for the authority's abilities.
     *
     * @param  bool  $allowed
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function forAuthority(Model $authority, $allowed = true)
    {
        return Models::ability()->where(function ($query) use ($authority, $allowed) {
            $query->whereExists(static::getRoleConstraint($authority, $allowed));
            $query->orWhereExists(static::getAuthorityConstraint($authority, $allowed));
            $query->orWhereExists(static::getEveryoneConstraint($allowed));
        });
    }

    /**
     * Get a query for the authority's forbidden abilities.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function forbiddenForAuthority(Model $authority)
    {
        return static::forAuthority($authority, false);
    }

    /**
     * Get a constraint for abilities that have been granted to the given authority through a role.
     *
     * @param  bool  $allowed
     * @return \Closure
     */
    protected static function getRoleConstraint(Model $authority, $allowed)
    {
        return function ($query) use ($authority, $allowed) {
            $permissions = Models::table('permissions');
            $abilities = Models::table('abilities');
            $roles = Models::table('roles');

            $query->from($roles)
                ->join($permissions, $roles.'.id', '=', $permissions.'.entity_id')
                ->whereColumn("{$permissions}.ability_id", "{$abilities}.id")
                ->where($permissions.'.forbidden', ! $allowed)
                ->where($permissions.'.entity_type', Models::role()->getMorphClass());

            Models::scope()->applyToModelQuery($query, $roles);
            Models::scope()->applyToRelationQuery($query, $permissions);

            $query->where(function ($query) use ($authority) {
                $query->whereExists(static::getAuthorityRoleConstraint($authority));
            });
        };
    }

    /**
     * Get a constraint for roles that are assigned to the given authority.
     *
     * @return \Closure
     */
    protected static function getAuthorityRoleConstraint(Model $authority)
    {
        return function ($query) use ($authority) {
            $pivot = Models::table('assigned_roles');
            $roles = Models::table('roles');
            $table = $authority->getTable();

            $query->from($table)
                ->join($pivot, "{$table}.{$authority->getKeyName()}", '=', $pivot.'.entity_id')
                ->whereColumn("{$pivot}.role_id", "{$roles}.id")
                ->where($pivot.'.entity_type', $authority->getMorphClass())
                ->where("{$table}.{$authority->getKeyName()}", $authority->getKey());

            Models::scope()->applyToModelQuery($query, $roles);
            Models::scope()->applyToRelationQuery($query, $pivot);
        };
    }

    /**
     * Get a constraint for abilities that have been granted to the given authority.
     *
     * @param  bool  $allowed
     * @return \Closure
     */
    protected static function getAuthorityConstraint(Model $authority, $allowed)
    {
        return function ($query) use ($authority, $allowed) {
            $permissions = Models::table('permissions');
            $abilities = Models::table('abilities');
            $table = $authority->getTable();

            $query->from($table)
                ->join($permissions, "{$table}.{$authority->getKeyName()}", '=', $permissions.'.entity_id')
                ->whereColumn("{$permissions}.ability_id", "{$abilities}.id")
                ->where("{$permissions}.forbidden", ! $allowed)
                ->where("{$permissions}.entity_type", $authority->getMorphClass())
                ->where("{$table}.{$authority->getKeyName()}", $authority->getKey());

            Models::scope()->applyToModelQuery($query, $abilities);
            Models::scope()->applyToRelationQuery($query, $permissions);
        };
    }

    /**
     * Get a constraint for abilities that have been granted to everyone.
     *
     * @param  bool  $allowed
     * @return \Closure
     */
    protected static function getEveryoneConstraint($allowed)
    {
        return function ($query) use ($allowed) {
            $permissions = Models::table('permissions');
            $abilities = Models::table('abilities');

            $query->from($permissions)
                ->whereColumn("{$permissions}.ability_id", "{$abilities}.id")
                ->where("{$permissions}.forbidden", ! $allowed)
                ->whereNull('entity_id');

            Models::scope()->applyToRelationQuery($query, $permissions);
        };
    }
}
