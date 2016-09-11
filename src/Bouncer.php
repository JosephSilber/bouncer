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
use Silber\Bouncer\Conductors\ChecksRole;
use Silber\Bouncer\Conductors\AssignsRole;
use Silber\Bouncer\Conductors\RemovesRole;
use Silber\Bouncer\Conductors\GivesAbility;
use Silber\Bouncer\Conductors\RemovesAbility;

class Bouncer
{
    /**
     * The bouncer clipboard instance.
     *
     * @var \Silber\Bouncer\CachedClipboard
     */
    protected $clipboard;

    /**
     * The bouncer seeder instance.
     *
     * @var \Silber\Bouncer\Seeder
     */
    protected $seeder;

    /**
     * The access gate instance.
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate|null
     */
    protected $gate;

    /**
     * Constructor.
     *
     * @param \Silber\Bouncer\CachedClipboard  $clipboard
     * @param \Silber\Bouncer\Seeder  $seeder
     */
    public function __construct(CachedClipboard $clipboard, Seeder $seeder)
    {
        $this->clipboard = $clipboard;
        $this->seeder = $seeder;
    }

    /**
     * Register a seeder callback.
     *
     * @param  \Closure|string  $seeder
     * @return $this
     */
    public function seeder($seeder)
    {
        $this->seeder->register($seeder);

        return $this;
    }

    /**
     * Run the registered seeders.
     *
     * @return $this
     */
    public function seed()
    {
        $this->seeder->run();

        return $this;
    }

    /**
     * Start a chain, to allow the given role a ability.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\GivesAbility
     */
    public function allow($role)
    {
        return new GivesAbility($role);
    }

    /**
     * Start a chain, to disallow the given role a ability.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\RemovesAbility
     */
    public function disallow($role)
    {
        return new RemovesAbility($role);
    }

    /**
     * Start a chain, to assign the given role to a model.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\AssignsRole
     */
    public function assign($role)
    {
        return new AssignsRole($role);
    }

    /**
     * Start a chain, to retract the given role from a model.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\RemovesRole
     */
    public function retract($role)
    {
        return new RemovesRole($role);
    }

    /**
     * Start a chain, to check if the given authority has a certain role.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Silber\Bouncer\Conductors\ChecksRole
     */
    public function is(Model $authority)
    {
        return new ChecksRole($authority, $this->clipboard);
    }

    /**
     * Use the given cache instance.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $cache
     * @return $this
     */
    public function cache(Store $cache = null)
    {
        $cache = $cache ?: $this->make(CacheRepository::class)->getStore();

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
        $this->clipboard->setCache(new NullStore);

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
        $this->clipboard->refresh($authority);

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
        $this->clipboard->refreshFor($authority);

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
     * Set the bouncer to be the exclusive authority on gate access.
     *
     * @param  bool  $boolean
     * @return $this
     */
    public function exclusive($boolean = true)
    {
        $this->clipboard->setExclusivity($boolean);

        return $this;
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    protected function make($abstract, array $parameters = [])
    {
        return Container::getInstance()->make($abstract, $parameters);
    }
}
