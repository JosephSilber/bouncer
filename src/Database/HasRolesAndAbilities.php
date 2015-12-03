<?php

namespace Silber\Bouncer\Database;

use Illuminate\Container\Container;

use Silber\Bouncer\Clipboard;
use Silber\Bouncer\Conductors\ChecksRole;
use Silber\Bouncer\Conductors\AssignsRole;
use Silber\Bouncer\Conductors\RemovesRole;
use Silber\Bouncer\Conductors\GivesAbility;
use Silber\Bouncer\Conductors\RemovesAbility;

trait HasRolesAndAbilities
{
    /**
     * The roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Models::classname(Role::class), 'user_roles');
    }

    /**
     * The Abilities relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function abilities()
    {
        return $this->belongsToMany(Models::classname(Ability::class), 'user_abilities');
    }

    /**
     * Get all of the user's abilities.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities()
    {
        return $this->getClipboardInstance()->getAbilities($this);
    }

    /**
     * Give abilities to the user.
     *
     * @param  mixed  $abilities
     * @return $this
     */
    public function allow($abilities)
    {
        (new GivesAbility($this))->to($abilities);

        return $this;
    }

    /**
     * Remove abilities from the user.
     *
     * @param  mixed  $abilities
     * @return $this
     */
    public function disallow($abilities)
    {
        (new RemovesAbility($this))->to($abilities);

        return $this;
    }

    /**
     * Assign the given role to the user.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return $this
     */
    public function assign($role)
    {
        (new AssignsRole($role))->to($this);

        return $this;
    }

    /**
     * Retract the given role from the user.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return $this
     */
    public function retract($role)
    {
        (new RemovesRole($role))->from($this);

        return $this;
    }

    /**
     * Check if the user has any of the given roles.
     *
     * @param  string  $role
     * @return bool
     */
    public function is($role)
    {
        $roles = func_get_args();

        $clipboard = $this->getClipboardInstance();

        return $clipboard->checkRole($this, $roles, 'or');
    }

    /**
     * Check if the user has none of the given roles.
     *
     * @param  string  $role
     * @return bool
     */
    public function isNot($role)
    {
        $roles = func_get_args();

        $clipboard = $this->getClipboardInstance();

        return $clipboard->checkRole($this, $roles, 'not');
    }

    /**
     * Check if the user has all of the given roles.
     *
     * @param  string  $role
     * @return bool
     */
    public function isAll($role)
    {
        $roles = func_get_args();

        $clipboard = $this->getClipboardInstance();

        return $clipboard->checkRole($this, $roles, 'and');
    }

    /**
     * Get an instance of the bouncer's clipboard.
     *
     * @return \Silber\Bouncer\Clipboard
     */
    protected function getClipboardInstance()
    {
        $container = Container::getInstance() ?: new Container;

        return $container->make(Clipboard::class);
    }
}
