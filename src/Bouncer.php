<?php

namespace Silber\Bouncer;

use RuntimeException;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;
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
     * The access gate instance.
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate|null
     */
    protected $gate;

    /**
     * @var string Role model class name
     */
    private $roleModelClass;

    /**
     * @var string Ability model class name
     */
    private $abilityModelClass;

    /**
     * Constructor.
     *
     * @param CachedClipboard $clipboard
     * @param string $roleModelClass
     * @param string $abilityModelClass
     */
    public function __construct(CachedClipboard $clipboard, $roleModelClass = 'Silber\Bouncer\Database\Role', $abilityModelClass = 'Silber\Bouncer\Database\Ability')
    {
        $this->clipboard = $clipboard;
        $this->roleModelClass = $roleModelClass;
        $this->abilityModelClass = $abilityModelClass;
    }

    /**
     * Start a chain, to allow the given role a ability.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\GivesAbility
     */
    public function allow($role)
    {
        return new GivesAbility($role, $this->roleModelClass, $this->abilityModelClass);
    }

    /**
     * Start a chain, to disallow the given role a ability.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\RemovesAbility
     */
    public function disallow($role)
    {
        return new RemovesAbility($role, $this->roleModelClass, $this->abilityModelClass);
    }

    /**
     * Start a chain, to assign the given role to a user.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\AssignsRole
     */
    public function assign($role)
    {
        return new AssignsRole($role, $this->roleModelClass);
    }

    /**
     * Start a chain, to retract the given role from a user.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\RemovesRole
     */
    public function retract($role)
    {
        return new RemovesRole($role, $this->roleModelClass);
    }

    /**
     * Start a chain, to check if the given user has a certain role.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Silber\Bouncer\Conductors\ChecksRole
     */
    public function is(Model $user)
    {
        return new ChecksRole($user, $this->clipboard);
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
     * Clear the cache.
     *
     * @param  null|\Illuminate\Database\Eloquent\Model  $user
     * @return $this
     */
    public function refresh(Model $user = null)
    {
        $this->clipboard->refresh($user);

        return $this;
    }

    /**
     * Clear the cache for the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return $this
     */
    public function refreshFor(Model $user)
    {
        $this->clipboard->refreshFor($user);

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
     * Determine if the given ability should be granted for the current user.
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
     * Determine if the given ability should be denied for the current user.
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
        return new $this->roleModelClass($attributes);
    }

    /**
     * Get an instance of the ability model.
     *
     * @param  array  $attributes
     * @return \Silber\Bouncer\Database\Ability
     */
    public function ability(array $attributes = [])
    {
        return new $this->abilityModelClass($attributes);
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
