<?php

namespace Silber\Bouncer;

use RuntimeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;

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
     * @var \Silber\Bouncer\Clipboard
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
     * @param \Silber\Bouncer\Clipboard
     */
    public function __construct(Clipboard $clipboard)
    {
        $this->clipboard = $clipboard;
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
     * Start a chain, to assign the given role to a user.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\AssignsRole
     */
    public function assign($role)
    {
        return new AssignsRole($role);
    }

    /**
     * Start a chain, to retract the given role from a user.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\RemovesRole
     */
    public function retract($role)
    {
        return new RemovesRole($role);
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
     * @return \Silber\Bouncer\Database\Role
     */
    public function role()
    {
        return new Role;
    }

    /**
     * Get an instance of the ability model.
     *
     * @return \Silber\Bouncer\Database\Ability
     */
    public function ability()
    {
        return new Ability;
    }
}
