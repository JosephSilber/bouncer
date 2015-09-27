<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Ability;

use Exception;
use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Collection;

class Clipboard
{
    /**
     * The tag used for caching.
     *
     * @var string
     */
    protected $tag = 'bouncer';

    /**
     * Whether we're truly caching the database results.
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $store;

    /**
     * Holds the cache of users' roles and abilities.
     *
     * @var array
     */
    protected $cache = [
        'abilities' => [],
        'roles' => [],
    ];

    /**
     * Set the cache instance.
     *
     * @param \Illuminate\Contracts\Cache\Store  $store
     */
    public function useCache(Store $store)
    {
        if (method_exists($store, 'tags')) {
            $store = $store->tags($this->tag);
        }

        $this->store = $store;
    }

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
        $abilities = $this->getUserAbilities($user)->toBase()->lists('slug');

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
        $available = $this->getUserRoles($user)->intersect($roles);

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
     * Get the given user's abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserAbilities(Model $user)
    {
        $id = $user->getKey();

        $key = $this->tag.'-abilities-'.$id;

        if (isset($this->cache['abilities'][$id])) {
            return $this->cache['abilities'][$id];
        }

        // If the developer has elected to use proper caching, we will first check
        // if the abilities have already been cached. If we find cached results,
        // we will decode them into a collection instance of eloquent models.
        if ($this->store && ($abilities = $this->store->get($key))) {
            return $this->cache['abilities'][$id] = $this->deserializeAbilities($abilities);
        }

        $abilities = $this->cache['abilities'][$id] = $this->getFreshUserAbilities($user);

        if ($this->store) {
            $this->store->forever($key, $this->serializeAbilities($abilities));
        }

        return $abilities;
    }

    /**
     * Get the given user's roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Support\Collection
     */
    public function getUserRoles(Model $user)
    {
        $id = $user->getKey();

        $key = $this->tag.'-roles-'.$id;

        if (isset($this->cache['roles'][$id])) {
            return $this->cache['roles'][$id];
        }

        // If the developer has elected to use proper caching, we'll
        // check if the roles have been cached. If we find cached
        // roles, we will use them for better query efficiency.
        if ($this->store && ($roles = $this->store->get($key))) {
            return $this->cache['roles'][$id] = $roles;
        }

        $roles = $this->cache['roles'][$id] = $this->getFreshUserRoles($user);

        if ($this->store) {
            $this->store->forever($key, $roles);
        }

        return $roles;
    }

    /**
     * Clear the cache.
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function refresh()
    {
        $this->cache['abilities'] = [];

        $this->cache['roles'] = [];

        if ($this->store) {
            if ( ! $this->store instanceof TaggedCache) {
                throw new Exception('Your cache driver does not support blanket cache purging. Use [refreshForUser] instead.');
            }

            $this->store->flush();
        }

        return $this;
    }

    /**
     * Clear the cache for the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return $this
     */
    public function refreshForUser(Model $user)
    {
        $id = $user->getKey();

        unset($this->cache['abilities'][$id]);

        unset($this->cache['roles'][$id]);

        if ($this->store) {
            $this->store->forget($this->tag.'-abilities-'.$id);

            $this->store->forget($this->tag.'-roles-'.$id);
        }

        return $this;
    }

    /**
     * Deserialize an array of abilities into a collection of models.
     *
     * @param  array  $abilities
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function deserializeAbilities(array $abilities)
    {
        return Ability::hydrate($abilities);
    }

    /**
     * Serialize a collection of ability models into a plain array.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $abilities
     * @return array
     */
    protected function serializeAbilities(Collection $abilities)
    {
        return $abilities->map(function ($ability) {
            return $ability->getAttributes();
        })->all();
    }

    /**
     * Get a fresh list of the user's abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getFreshUserAbilities(Model $user)
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

    /**
     * Get a fresh list of the given user's roles.
     *
     * @param  array|string  $role
     * @return \Illuminate\Support\Collection
     */
    protected function getFreshUserRoles(Model $user)
    {
        return $user->roles()->lists('name');
    }
}
