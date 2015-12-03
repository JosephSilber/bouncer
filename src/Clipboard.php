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

            if ($id = $this->checkGetId($user, $ability, $model)) {
                return $this->allow('Bouncer granted permission via ability #'.$id);
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
        return (bool) $this->checkGetId($user, $ability, $model);
    }

    /**
     * Determine if the given user has the given ability and return the ability ID.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return int|bool
     */
    protected function checkGetId(Model $user, $ability, $model = null)
    {
        $abilities = $this->getAbilities($user)->toBase()->lists('slug', 'id');

        $requested = $this->compileAbilitySlugs($ability, $model);

        foreach ($abilities as $id => $ability) {
            if (in_array($ability, $requested)) {
                return $id;
            }
        }

        return false;
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
        elseif ($boolean === 'not') {
            return $available->count() === 0;
        }

        return $available->count() == count((array) $roles);
    }

    /**
     * Compile a list of ability slugs that match the provided parameters.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return array
     */
    protected function compileAbilitySlugs($ability, $model)
    {
        if (is_null($model)) {
            return [strtolower($ability)];
        }

        return $this->compileModelAbilitySlugs($ability, $model);
    }

    /**
     * Compile a list of ability slugs that match the given model.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return array
     */
    protected function compileModelAbilitySlugs($ability, $model)
    {
        $model = $model instanceof Model ? $model : new $model;

        $slug = strtolower($ability.'-'.$model->getMorphClass());

        if ( ! $model->exists) {
            return [$slug];
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
        $query = Models::ability()->whereHas('roles', $this->getRoleUsersConstraint($user));

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
