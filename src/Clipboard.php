<?php

namespace Silber\Bouncer;

use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Ability;

class Clipboard
{
    /**
     * Holds the cache of user's abilities.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Determine if the given user has the given ability.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  \Silber\Bouncer\Database\Ability|string  $ability
     * @return bool
     */
    public function check(Model $user, $ability)
    {
        if ($ability instanceof Ability) {
            $ability = $ability->title;
        }

        return $this->getUserAbilities($user)->contains($ability);
    }

    /**
     * Get the given user's abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  bool  $fresh
     * @return \Illuminate\Support\Collection
     */
    public function getUserAbilities(Model $user, $fresh = false)
    {
        $id = $user->getKey();

        if ( ! isset($this->cache[$id]) || $fresh) {
            $this->cache[$id] = $this->fetchUserAbilities($user);
        }

        return $this->cache[$id];
    }

    /**
     * Clear the abilities cache.
     *
     * @return $this
     */
    public function refresh()
    {
        $this->cache = [];

        return $this;
    }

    /**
     * Clear the abilities cache for the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return $this
     */
    public function refreshForUser(Model $user)
    {
        unset($this->cache[$user->getKey()]);

        return $this;
    }

    /**
     * Fetch a list of the user's abilities from the database.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Support\Collection
     */
    protected function fetchUserAbilities(Model $user)
    {
        $query = Ability::whereHas('roles', $this->getRoleUsersConstraint($user));

        $query->orWhereHas('users', $this->getUserConstraint($user));

        return $query->lists('title');
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
