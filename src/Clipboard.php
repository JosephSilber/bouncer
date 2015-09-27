<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Ability;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;

class Clipboard
{
    /**
     * Register the clipboard at the given gate.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function registerAt(Gate $gate)
    {
        $gate->before(function ($user, $ability, $model = null, $additional = null) {
            if ( ! is_null($additional)) {
                return;
            }

            if ($this->check($user, $ability, $model)) {
                return true;
            }
        });
    }

    /**
     * Determine if the given user has the given ability.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return bool
     */
    public function check(Model $user, $ability, $model = null)
    {
        $abilities = $this->getAbilities($user)->toBase()->lists('slug');

        $requested = $this->compileAbilitySlugs($ability, $model);

        return $abilities->intersect($requested)->count() > 0;
    }

    /**
     * Check if a user has the given roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  array|string  $roles
     * @param  string  $boolean
     * @return bool
     */
    public function checkRole(Model $user, $roles, $boolean = 'or')
    {
        $available = $this->getRoles($user)->intersect($roles);

        if ($boolean == 'or') {
            return $available->count() > 0;
        }

        return $available->count() == count((array) $roles);
    }

    /**
     * Compile a list of ability slugs that match the provided parameters.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return array|string
     */
    protected function compileAbilitySlugs($ability, $model)
    {
        if (is_null($model)) {
            return strtolower($ability);
        }

        return $this->compileModelAbilitySlugs($ability, $model);
    }

    /**
     * Compile a list of ability slugs that match the given model.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return array|string
     */
    protected function compileModelAbilitySlugs($ability, $model)
    {
        $model = $model instanceof Model ? $model : new $model;

        $slug = strtolower($ability.'-'.$model->getMorphClass());

        if ( ! $model->exists) {
            return $slug;
        }

        return [$slug, $slug.'-'.$model->getKey()];
    }

    /**
     * Get the given user's roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Support\Collection
     */
    public function getRoles(Model $user)
    {
        return $user->roles()->lists('name');
    }

    /**
     * Get a list of the user's abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities(Model $user)
    {
        $query = Ability::whereHas('roles', $this->getRoleUsersConstraint($user));

        return $query->orWhereHas('users', $this->getUserConstraint($user))->get();
    }

    /**
     * Constrain a roles query by the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Closure
     */
    protected function getRoleUsersConstraint(Model $user)
    {
        return function ($query) use ($user) {
            $query->whereHas('users', $this->getUserConstraint($user));
        };
    }

    /**
     * Constrain a related query to the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Closure
     */
    protected function getUserConstraint(Model $user)
    {
        return function ($query) use ($user) {
            $column = "{$user->getTable()}.{$user->getKeyName()}";

            $query->where($column, $user->getKey());
        };
    }
}
