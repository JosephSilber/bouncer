<?php

namespace Silber\Bouncer\Database\Queries;

use Silber\Bouncer\Database\Models;

use Illuminate\Database\Eloquent\Model;

class Abilities
{
    /**
     * Get a list of the authority's abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getForAuthority(Model $authority)
    {
        return Models::ability()
                     ->whereExists($this->getRoleConstraint($authority))
                     ->orWhereExists($this->getAuthorityConstraint($authority))
                     ->get();
    }

    /**
     * Get a constraint for abilities that have been granted to the given authority through a role.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Closure
     */
    protected function getRoleConstraint(Model $authority)
    {
        return function ($query) use ($authority) {
            $permissions = Models::table('permissions');
            $abilities   = Models::table('abilities');
            $roles       = Models::table('roles');

            $query->from($roles)
                  ->join($permissions, $roles.'.id', '=', $permissions.'.entity_id')
                  ->whereRaw($permissions.".ability_id = ".$abilities.".id")
                  ->where($permissions.".entity_type", Models::role()->getMorphClass());

            $query->where(function ($query) use ($roles, $authority) {
                $query->whereExists($this->getAuthorityRoleConstraint($authority));

                $query->orWhere('level', '<', function ($query) use ($roles, $authority) {
                    $query->selectRaw('max(level)')
                          ->from($roles)
                          ->whereExists($this->getAuthorityRoleConstraint($authority));
                });
            });
        };
    }

    /**
     * Get a constraint for roles that are assigned to the given authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Closure
     */
    protected function getAuthorityRoleConstraint(Model $authority)
    {
        return function ($query) use ($authority) {
            $pivot = Models::table('assigned_roles');
            $roles = Models::table('roles');
            $table = $authority->getTable();

            $query->from($table)
                  ->join($pivot, $table.'.'.$authority->getKeyName(), '=', $pivot.'.entity_id')
                  ->whereRaw($pivot.'.role_id = '.$roles.'.id')
                  ->where($pivot.'.entity_type', $authority->getMorphClass())
                  ->where($table.'.id', $authority->getKey());
        };
    }

    /**
     * Get a constraint for abilities that have been granted to the given authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Closure
     */
    protected function getAuthorityConstraint(Model $authority)
    {
        return function ($query) use ($authority) {
            $permissions = Models::table('permissions');
            $abilities   = Models::table('abilities');
            $table       = $authority->getTable();

            $query->from($table)
                  ->join($permissions, $table.'.id', '=', $permissions.'.entity_id')
                  ->whereRaw("{$permissions}.ability_id = {$abilities}.id")
                  ->where("{$permissions}.entity_type", $authority->getMorphClass())
                  ->where("{$table}.{$authority->getKeyName()}", $authority->getKey());
        };
    }
}
