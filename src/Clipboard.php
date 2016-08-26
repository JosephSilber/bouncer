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
        $gate->before(function ($authority, $ability, $arguments = [], $additional = null) {
            list($model, $additional) = $this->parseGateArguments($arguments, $additional);

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
     * Parse the arguments we got from the gate.
     *
     * @param  mixed  $arguments
     * @param  mixed  $additional
     * @return array
     */
    protected function parseGateArguments($arguments, $additional)
    {
        // The way arguments are passed into the gate's before callback has changed in Laravel
        // in the middle of the 5.2 release. Before, arguments were spread out. Now they're
        // all supplied in a single array instead. We will normalize it into two values.
        if ( ! is_null($additional)) {
            return [$arguments, $additional];
        }

        if (is_array($arguments)) {
            return [
                isset($arguments[0]) ? $arguments[0] : null,
                isset($arguments[1]) ? $arguments[1] : null,
            ];
        }

        return [$arguments, null];
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
        $abilities = $this->getAbilities($authority)->toBase()->pluck('identifier', 'id');

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
        $ability = strtolower($ability);

        if (is_null($model)) {
            return [$ability, '*-*', '*'];
        }

        return $this->compileModelAbilityIdentifiers($ability, $model);
    }

    /**
     * Compile a list of ability identifiers that match the given model.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return array
     */
    protected function compileModelAbilityIdentifiers($ability, $model)
    {
        if ($model == '*') {
            return ["{$ability}-*", "*-*"];
        }

        $model = $model instanceof Model ? $model : new $model;

        $type = strtolower($model->getMorphClass());

        $abilities = [
            "{$ability}-{$type}",
            "{$ability}-*",
            "*-{$type}",
            "*-*",
        ];

        if ($model->exists) {
            $abilities[] = "{$ability}-{$type}-{$model->getKey()}";
            $abilities[] = "*-{$type}-{$model->getKey()}";
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
        $collection = $authority->roles()->get(['name'])->pluck('name');

        // In Laravel 5.1, "pluck" returns an Eloquent collection,
        // so we call "toBase" on it. In 5.2, "pluck" returns a
        // base instance, so there is no "toBase" available.
        if (method_exists($collection, 'toBase')) {
            $collection = $collection->toBase();
        }

        return $collection;
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
