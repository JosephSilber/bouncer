<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\HandlesAuthorization;

class Clipboard
{
    use HandlesAuthorization;

    /**
     * Whether the bouncer is the exclusive authority on gate access.
     *
     * @var bool
     */
    protected $exclusive = false;

    /**
     * Register the clipboard at the given gate.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function registerAt(Gate $gate)
    {
        $gate->before(function ($authority, $ability, $model = null, $additional = null) {
            if ( ! is_null($additional)) {
                return;
            }

            if ($id = $this->checkGetId($authority, $ability, $model)) {
                return $this->allow('Bouncer granted permission via ability #'.$id);
            }

            if ($this->exclusive) {
                return false;
            }
        });
    }

    /**
     * Set whether the bouncer is the exclusive authority on gate access.
     *
     * @param  bool  $boolean
     * @return $this
     */
    public function setExclusivity($boolean)
    {
        $this->exclusive = $boolean;
    }

    /**
     * Determine if the given authority has the given ability.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return bool
     */
    public function check(Model $authority, $ability, $model = null)
    {
        return (bool) $this->checkGetId($authority, $ability, $model);
    }

    /**
     * Determine if the given authority has the given ability, and return the ability ID.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return int|bool
     */
    protected function checkGetId(Model $authority, $ability, $model = null)
    {
        $abilities = $this->getAbilities($authority)->toBase()->lists('identifier', 'id');

        $requested = $this->compileAbilityIdentifiers($ability, $model);

        foreach ($abilities as $id => $ability) {
            if (in_array($ability, $requested)) {
                return $id;
            }
        }

        return false;
    }

    /**
     * Check if an authority has the given roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  array|string  $roles
     * @param  string  $boolean
     * @return bool
     */
    public function checkRole(Model $authority, $roles, $boolean = 'or')
    {
        $available = $this->getRoles($authority)->intersect($roles);

        if ($boolean == 'or') {
            return $available->count() > 0;
        } elseif ($boolean === 'not') {
            return $available->count() === 0;
        }

        return $available->count() == count((array) $roles);
    }

    /**
     * Compile a list of ability identifiers that match the provided parameters.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return array
     */
    protected function compileAbilityIdentifiers($ability, $model)
    {
        if (is_null($model)) {
            return [strtolower($ability), '*'];
        }

        return $this->compileModelAbilityIdentifiers($ability, $model);
    }

    /**
     * Compile a list of ability identifiers that match the given model.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return array
     */
    protected function compileModelAbilityIdentifiers($ability, $model)
    {
        $model = $model instanceof Model ? $model : new $model;

        $suffix = strtolower('-'.$model->getMorphClass());

        $abilities = [
            $ability.$suffix,
            '*'.$suffix,
            '*',
        ];

        if ($model->exists) {
            $abilities[] = $ability.$suffix.'-'.$model->getKey();
            $abilities[] = '*'.$suffix.'-'.$model->getKey();
        }

        return $abilities;
    }

    /**
     * Get the given authority's roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Illuminate\Support\Collection
     */
    public function getRoles(Model $authority)
    {
        return $authority->roles()->lists('name');
    }

    /**
     * Get a list of the authority's abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities(Model $authority)
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

            $query->whereExists($this->getAuthorityRoleConstraint($authority));
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
