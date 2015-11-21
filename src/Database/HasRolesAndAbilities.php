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
        $roleModelClass = $this->roleModelClass ?: Role::class;
        return $this->belongsToMany(
            $roleModelClass,
            'user_roles',
            'user_id',
            'role_id'
        );
    }

    /**
     * The Abilities relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function abilities()
    {
        $abilityModelClass = $this->abilityModelClass ?: Ability::class;
        return $this->belongsToMany(
            $abilityModelClass,
            'user_abilities',
            'user_id',
            'ability_id'
        );
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
        $roleModelClass = $this->roleModelClass ?: Role::class;
        $abilityModelClass = $this->abilityModelClass ?: Ability::class;

        (new GivesAbility($this, $roleModelClass, $abilityModelClass))->to($abilities);

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
        $roleModelClass = $this->roleModelClass ?: Role::class;
        $abilityModelClass = $this->abilityModelClass ?: Ability::class;

        (new RemovesAbility($this, $roleModelClass, $abilityModelClass))->to($abilities);

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
        $roleModelClass = $this->roleModelClass ?: Role::class;

        (new AssignsRole($role, $roleModelClass))->to($this);

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
        $roleModelClass = $this->roleModelClass ?: Role::class;
        $abilityModelClass = $this->abilityModelClass ?: Ability::class;

        (new RemovesRole($role, $roleModelClass, $abilityModelClass))->from($this);

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
