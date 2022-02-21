<?php

namespace Silber\Bouncer;

use RuntimeException;
use Illuminate\Cache\NullStore;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

use Silber\Bouncer\Contracts\Scope;
use Silber\Bouncer\Database\Models;

class Bouncer
{
    /**
     * The bouncer guard instance.
     *
     * @var \Silber\Bouncer\Guard
     */
    protected $guard;

    /**
     * The access gate instance.
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate|null
     */
    protected $gate;

    /**
     * Constructor.
     *
     * @param \Silber\Bouncer\Guard  $guard
     */
    public function __construct(Guard $guard)
    {
        $this->guard = $guard;
    }

    /**
     * Create a new Bouncer instance.
     *
     * @param  mixed  $user
     * @return static
     */
    public static function create($user = null)
    {
        return static::make($user)->create();
    }

    /**
     * Create a bouncer factory instance.
     *
     * @param  mixed  $user
     * @return \Silber\Bouncer\Factory
     */
    public static function make($user = null)
    {
        return new Factory($user);
    }

    /**
     * Start a chain, to allow the given authority an ability.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $authority
     * @return \Silber\Bouncer\Conductors\GivesAbilities
     */
    public function allow($authority)
    {
        return new Conductors\GivesAbilities($authority);
    }

    /**
     * Start a chain, to allow everyone an ability.
     *
     * @return \Silber\Bouncer\Conductors\GivesAbilities
     */
    public function allowEveryone()
    {
        return new Conductors\GivesAbilities();
    }

    /**
     * Start a chain, to disallow the given authority an ability.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $authority
     * @return \Silber\Bouncer\Conductors\RemovesAbilities
     */
    public function disallow($authority)
    {
        return new Conductors\RemovesAbilities($authority);
    }

    /**
     * Start a chain, to disallow everyone the an ability.
     *
     * @return \Silber\Bouncer\Conductors\RemovesAbilities
     */
    public function disallowEveryone()
    {
        return new Conductors\RemovesAbilities();
    }

    /**
     * Start a chain, to forbid the given authority an ability.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $authority
     * @return \Silber\Bouncer\Conductors\GivesAbilities
     */
    public function forbid($authority)
    {
        return new Conductors\ForbidsAbilities($authority);
    }

    /**
     * Start a chain, to forbid everyone an ability.
     *
     * @return \Silber\Bouncer\Conductors\GivesAbilities
     */
    public function forbidEveryone()
    {
        return new Conductors\ForbidsAbilities();
    }

    /**
     * Start a chain, to unforbid the given authority an ability.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $authority
     * @return \Silber\Bouncer\Conductors\RemovesAbilities
     */
    public function unforbid($authority)
    {
        return new Conductors\UnforbidsAbilities($authority);
    }

    /**
     * Start a chain, to unforbid an ability from everyone.
     *
     * @return \Silber\Bouncer\Conductors\RemovesAbilities
     */
    public function unforbidEveryone()
    {
        return new Conductors\UnforbidsAbilities();
    }

    /**
     * Start a chain, to assign the given role to a model.
     *
     * @param  \Silber\Bouncer\Database\Role|\Illuminate\Support\Collection|string  $roles
     * @return \Silber\Bouncer\Conductors\AssignsRoles
     */
    public function assign($roles)
    {
        return new Conductors\AssignsRoles($roles);
    }

    /**
     * Start a chain, to retract the given role from a model.
     *
     * @param  \Illuminate\Support\Collection|\Silber\Bouncer\Database\Role|string  $roles
     * @return \Silber\Bouncer\Conductors\RemovesRoles
     */
    public function retract($roles)
    {
        return new Conductors\RemovesRoles($roles);
    }

    /**
     * Start a chain, to sync roles/abilities for the given authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $authority
     * @return \Silber\Bouncer\Conductors\SyncsRolesAndAbilities
     */
    public function sync($authority)
    {
        return new Conductors\SyncsRolesAndAbilities($authority);
    }

    /**
     * Start a chain, to check if the given authority has a certain role.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Silber\Bouncer\Conductors\ChecksRoles
     */
    public function is(Model $authority)
    {
        return new Conductors\ChecksRoles($authority, $this->getClipboard());
    }

    /**
     * Get the clipboard instance.
     *
     * @return \Silber\Bouncer\Contracts\Clipboard
     */
    public function getClipboard()
    {
        return $this->guard->getClipboard();
    }

    /**
     * Set the clipboard instance used by bouncer.
     *
     * Will also register the given clipboard with the container.
     *
     * @param  \Silber\Bouncer\Contracts\Clipboard
     * @return $this
     */
    public function setClipboard(Contracts\Clipboard $clipboard)
    {
        $this->guard->setClipboard($clipboard);

        return $this->registerClipboardAtContainer();
    }

    /**
     * Register the guard's clipboard at the container.
     *
     * @return $this
     */
    public function registerClipboardAtContainer()
    {
        $clipboard = $this->guard->getClipboard();

        Container::getInstance()->instance(Contracts\Clipboard::class, $clipboard);

        return $this;
    }

    /**
     * Use a cached clipboard with the given cache instance.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $cache
     * @return $this
     */
    public function cache(Store $cache = null)
    {
        $cache = $cache ?: $this->resolve(CacheRepository::class)->getStore();

        if ($this->usesCachedClipboard()) {
            $this->guard->getClipboard()->setCache($cache);

            return $this;
        }

        return $this->setClipboard(new CachedClipboard($cache));
    }

    /**
     * Fully disable all query caching.
     *
     * @return $this
     */
    public function dontCache()
    {
        return $this->setClipboard(new Clipboard);
    }

    /**
     * Clear the cache.
     *
     * @param  null|\Illuminate\Database\Eloquent\Model  $authority
     * @return $this
     */
    public function refresh(Model $authority = null)
    {
        if ($this->usesCachedClipboard()) {
            $this->getClipboard()->refresh($authority);
        }

        return $this;
    }

    /**
     * Clear the cache for the given authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return $this
     */
    public function refreshFor(Model $authority)
    {
        if ($this->usesCachedClipboard()) {
            $this->getClipboard()->refreshFor($authority);
        }

        return $this;
    }

    /**
     * Set the access gate instance.
     *
     * @param \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return $this
     */
    public function setGate(Gate $gate)
    {
        $this->gate = $gate;

        return $this;
    }

    /**
     * Get the gate instance.
     *
     * @return \Illuminate\Contracts\Auth\Access\Gate|null
     */
    public function getGate()
    {
        return $this->gate;
    }

    /**
     * Get the gate instance. Throw if not set.
     *
     * @return \Illuminate\Contracts\Auth\Access\Gate
     *
     * @throws \RuntimeException
     */
    public function gate()
    {
        if (is_null($this->gate)) {
            throw new RuntimeException('The gate instance has not been set.');
        }

        return $this->gate;
    }

    /**
     * Determine whether the clipboard used is a cached clipboard.
     *
     * @return bool
     */
    public function usesCachedClipboard()
    {
        return $this->guard->usesCachedClipboard();
    }

    /**
     * Define a new ability using a callback.
     *
     * @param  string  $ability
     * @param  callable|string  $callback
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function define($ability, $callback)
    {
        return $this->gate()->define($ability, $callback);
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($ability, $arguments = [])
    {
        return $this->gate()->authorize($ability, $arguments);
    }

    /**
     * Determine if the given ability is allowed.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function can($ability, $arguments = [])
    {
        return $this->gate()->allows($ability, $arguments);
    }

    /**
     * Determine if any of the given abilities are allowed.
     *
     * @param  array  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function canAny($abilities, $arguments = [])
    {
        return $this->gate()->any($abilities, $arguments);
    }

    /**
     * Determine if the given ability is denied.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function cannot($ability, $arguments = [])
    {
        return $this->gate()->denies($ability, $arguments);
    }

    /**
     * Determine if the given ability is allowed.
     *
     * Alias for the "can" method.
     *
     * @deprecated
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function allows($ability, $arguments = [])
    {
        return $this->can($ability, $arguments);
    }

    /**
     * Determine if the given ability is denied.
     *
     * Alias for the "cannot" method.
     *
     * @deprecated
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function denies($ability, $arguments = [])
    {
        return $this->cannot($ability, $arguments);
    }

    /**
     * Get an instance of the role model.
     *
     * @param  array  $attributes
     * @return \Silber\Bouncer\Database\Role
     */
    public function role(array $attributes = [])
    {
        return Models::role($attributes);
    }

    /**
     * Get an instance of the ability model.
     *
     * @param  array  $attributes
     * @return \Silber\Bouncer\Database\Ability
     */
    public function ability(array $attributes = [])
    {
        return Models::ability($attributes);
    }

    /**
     * Set Bouncer to run its checks before the policies.
     *
     * @param  bool  $boolean
     * @return $this
     */
    public function runBeforePolicies($boolean = true)
    {
        $this->guard->slot($boolean ? 'before' : 'after');

        return $this;
    }

    /**
     * Register an attribute/callback to determine if a model is owned by a given authority.
     *
     * @param  string|\Closure  $model
     * @param  string|\Closure|null  $attribute
     * @return $this
     */
    public function ownedVia($model, $attribute = null)
    {
        Models::ownedVia($model, $attribute);

        return $this;
    }

    /**
     * Set the model to be used for abilities.
     *
     * @param  string  $model
     * @return $this
     */
    public function useAbilityModel($model)
    {
        Models::setAbilitiesModel($model);

        return $this;
    }

    /**
     * Set the model to be used for roles.
     *
     * @param  string  $model
     * @return $this
     */
    public function useRoleModel($model)
    {
        Models::setRolesModel($model);

        return $this;
    }

    /**
     * Set the model to be used for users.
     *
     * @param  string  $model
     * @return $this
     */
    public function useUserModel($model)
    {
        Models::setUsersModel($model);

        return $this;
    }

    /**
     * Set custom table names.
     *
     * @param  array  $map
     * @return $this
     */
    public function tables(array $map)
    {
        Models::setTables($map);

        return $this;
    }

    /**
     * Get the model scoping instance.
     *
     * @param  \Silber\Bouncer\Contracts\Scope|null  $scope
     * @return mixed
     */
    public function scope(Scope $scope = null)
    {
        return Models::scope($scope);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    protected function resolve($abstract, array $parameters = [])
    {
        return Container::getInstance()->make($abstract, $parameters);
    }
}
