<?php

namespace Silber\Bouncer;

use RuntimeException;
use Illuminate\Cache\NullStore;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

use Silber\Bouncer\Seed\Seeder;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Conductors\ChecksRoles;
use Silber\Bouncer\Conductors\AssignsRoles;
use Silber\Bouncer\Conductors\RemovesRoles;
use Silber\Bouncer\Conductors\GivesAbilities;
use Silber\Bouncer\Conductors\RemovesAbilities;
use Silber\Bouncer\Conductors\ForbidsAbilities;
use Silber\Bouncer\Conductors\UnforbidsAbilities;
use Silber\Bouncer\Conductors\SyncsRolesAndAbilities;
use Silber\Bouncer\Contracts\Clipboard as ClipboardContract;
use Silber\Bouncer\Contracts\CachedClipboard as CachedClipboardContract;

class Bouncer
{
    /**
     * The bouncer clipboard instance.
     *
     * @var \Silber\Bouncer\Contracts\Clipboard
     */
    protected $clipboard;

    /**
     * The access gate instance.
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate|null
     */
    protected $gate;

    /**
     * Constructor.
     *
     * @param \Silber\Bouncer\Contracts\Clipboard  $clipboard
     */
    public function __construct(ClipboardContract $clipboard)
    {
        $this->clipboard = $clipboard;
    }

    /**
     * Create a new Bouncer instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $user
     * @return static
     */
    public static function create(Model $user = null)
    {
        return static::make()->create($user);
    }

    /**
     * Create a bouncer factory instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $user
     * @return \Silber\Bouncer\Factory.
     */
    public static function make(Model $user = null)
    {
        return new Factory($user);
    }

    /**
     * Register a seeder callback.
     *
     * @param  \Closure|string  $seeder
     * @return $this
     */
    public function seeder($seeder)
    {
        $this->resolve(Seeder::class)->register($seeder);

        return $this;
    }

    /**
     * Run the registered seeders.
     *
     * @return $this
     */
    public function seed()
    {
        $this->resolve(Seeder::class)->run();

        return $this;
    }

    /**
     * Start a chain, to allow the given authority an ability.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $authority
     * @return \Silber\Bouncer\Conductors\GivesAbilities
     */
    public function allow($authority)
    {
        return new GivesAbilities($authority);
    }

    /**
     * Start a chain, to disallow the given authority an ability.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $authority
     * @return \Silber\Bouncer\Conductors\RemovesAbilities
     */
    public function disallow($authority)
    {
        return new RemovesAbilities($authority);
    }

    /**
     * Start a chain, to forbid the given authority an ability.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $authority
     * @return \Silber\Bouncer\Conductors\GivesAbilities
     */
    public function forbid($authority)
    {
        return new ForbidsAbilities($authority);
    }

    /**
     * Start a chain, to unforbid the given authority an ability.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $authority
     * @return \Silber\Bouncer\Conductors\RemovesAbilities
     */
    public function unforbid($authority)
    {
        return new UnforbidsAbilities($authority);
    }

    /**
     * Start a chain, to assign the given role to a model.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\AssignsRoles
     */
    public function assign($role)
    {
        return new AssignsRoles($role);
    }

    /**
     * Start a chain, to retract the given role from a model.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\RemovesRoles
     */
    public function retract($role)
    {
        return new RemovesRoles($role);
    }

    /**
     * Start a chain, to sync roles/abilities for the given authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Silber\Bouncer\Conductors\SyncsRolesAndAbilities
     */
    public function sync(Model $authority)
    {
        return new SyncsRolesAndAbilities($authority);
    }

    /**
     * Start a chain, to check if the given authority has a certain role.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Silber\Bouncer\Conductors\ChecksRoles
     */
    public function is(Model $authority)
    {
        return new ChecksRoles($authority, $this->clipboard);
    }

    /**
     * Use the given cache instance.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $cache
     * @return $this
     */
    public function cache(Store $cache = null)
    {
        if (! $this->usesCachedClipboard()) {
            throw new RuntimeException('To use caching, you must use an instance of CachedClipboard.');
        }

        $cache = $cache ?: $this->resolve(CacheRepository::class)->getStore();

        $this->clipboard->setCache($cache);

        return $this;
    }

    /**
     * Fully disable all query caching.
     *
     * @return $this
     */
    public function dontCache()
    {
        if ($this->usesCachedClipboard()) {
            $this->clipboard->setCache(new NullStore);
        }

        return $this;
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
            $this->clipboard->refresh($authority);
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
            $this->clipboard->refreshFor($authority);
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
     *
     * @throws \RuntimeException
     */
    public function getGate($throw = false)
    {
        if ($this->gate) {
            return $this->gate;
        }

        if ($throw) {
            throw new RuntimeException('The gate instance has not been set.');
        }

        return null;
    }

    /**
     * Determine whether the clipboard used is a cached clipboard.
     *
     * @return bool
     */
    public function usesCachedClipboard()
    {
        return $this->clipboard instanceof CachedClipboardContract;
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
        return $this->getGate(true)->define($ability, $callback);
    }

    /**
     * Determine if the given ability should be granted for the current authority.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function allows($ability, $arguments = [])
    {
        return $this->getGate(true)->allows($ability, $arguments);
    }

    /**
     * Determine if the given ability should be denied for the current authority.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function denies($ability, $arguments = [])
    {
        return $this->getGate(true)->denies($ability, $arguments);
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
     * Register an attribute/callback to determine if a model is owned by a given authority.
     *
     * @param  string|\Closure  $model
     * @param  string|\Closure|null  $attribute
     * @return void
     */
    public function ownedVia($model, $attribute = null)
    {
        Models::ownedVia($model, $attribute);

        return $this;
    }

    /**
     * Set the model to be used for abilities.
     *
     * @param string  $model
     */
    public static function useAbilityModel($model)
    {
        Models::setAbilitiesModel($model);
    }

    /**
     * Set the model to be used for roles.
     *
     * @param string  $model
     */
    public static function useRoleModel($model)
    {
        Models::setRolesModel($model);
    }

    /**
     * Set the model to be used for users.
     *
     * @param string  $model
     */
    public static function useUserModel($model)
    {
        Models::setUsersModel($model);
    }

    /**
     * Set custom table names.
     *
     * @param  array  $map
     * @return void
     */
    public static function tables(array $map)
    {
        Models::setTables($map);
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
