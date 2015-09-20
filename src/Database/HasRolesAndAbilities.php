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
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * The Abilities relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function abilities()
    {
        return $this->belongsToMany(Ability::class, 'user_Abilities');
    }

    /**
     * Get a list of the current user's abilities.
     *
     * @return \Illuminate\Support\Collection
     */
    public function listAbilities()
    {
        return $this->getClipboardInstance()->getUserAbilities($this);
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
     * Check if the user has the given role.
     *
     * @param  string|array  $role
     * @param  string  $boolean
     * @return bool
     */
    public function is($role, $boolean = 'or')
    {
        $checker = new ChecksRole($this, $this->getClipboardInstance());

        return $checker->a($role, $boolean);
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
