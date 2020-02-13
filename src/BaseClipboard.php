<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Queries\Abilities;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;

abstract class BaseClipboard implements Contracts\Clipboard
{
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
     * Check if an authority has the given roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  array|string  $roles
     * @param  string  $boolean
     * @return bool
     */
    public function checkRole(Model $authority, $roles, $boolean = 'or')
    {
        $count = $this->countMatchingRoles($authority, $roles);

        if ($boolean == 'or') {
            return $count > 0;
        } elseif ($boolean === 'not') {
            return $count === 0;
        }

        return $count == count((array) $roles);
    }

    /**
     * Count the authority's roles matching the given roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  array|string  $roles
     * @return int
     */
    protected function countMatchingRoles($authority, $roles)
    {
        $lookups = $this->getRolesLookup($authority);

        return count(array_filter($roles, function ($role) use ($lookups) {
            switch (true) {
                case is_string($role):
                    return $lookups['names']->has($role);
                case is_numeric($role):
                    return $lookups['ids']->has($role);
                case $role instanceof Model:
                    return $lookups['ids']->has($role->getKey());
            }

            throw new InvalidArgumentException('Invalid model identifier');
        }));
    }

    /**
     * Get the given authority's roles' IDs and names.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return array
     */
    public function getRolesLookup(Model $authority)
    {
        $roles = $authority->roles()->get([
            'name', Models::role()->getQualifiedKeyName()
        ])->pluck('name', Models::role()->getKeyName());

        return ['ids' => $roles, 'names' => $roles->flip()];
    }

    /**
     * Get the given authority's roles' names.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Illuminate\Support\Collection
     */
    public function getRoles(Model $authority)
    {
        return $this->getRolesLookup($authority)['names']->keys();
    }

    /**
     * Get a list of the authority's abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  bool  $allowed
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities(Model $authority, $allowed = true)
    {
        return Abilities::forAuthority($authority, $allowed)->get();
    }

    /**
     * Get a list of the authority's forbidden abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getForbiddenAbilities(Model $authority)
    {
        return $this->getAbilities($authority, false);
    }

    /**
     * Determine whether the authority owns the given model.
     *
     * @return bool
     */
    public function isOwnedBy($authority, $model)
    {
        return $model instanceof Model && Models::isOwnedBy($authority, $model);
    }
}
