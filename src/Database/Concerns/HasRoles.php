<?php

namespace Silber\Bouncer\Database\Concerns;

use Illuminate\Container\Container;

use Silber\Bouncer\Clipboard;
use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Conductors\AssignsRoles;
use Silber\Bouncer\Conductors\RemovesRoles;
use Silber\Bouncer\Database\Queries\Roles as RolesQuery;

trait HasRoles
{
    /**
     * The roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function roles()
    {
        $relation = $this->morphToMany(
            Models::classname(Role::class),
            'entity',
            Models::table('assigned_roles')
        );

        return Models::scope()->applyToRelation($relation);
    }

    /**
     * Assign the given roles to the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string|array  $roles
     * @return $this
     */
    public function assign($roles)
    {
        (new AssignsRoles($roles))->to($this);

        return $this;
    }

    /**
     * Retract the given roles from the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string|array  $roles
     * @return $this
     */
    public function retract($roles)
    {
        (new RemovesRoles($roles))->from($this);

        return $this;
    }

    /**
     * Check if the model has any of the given roles.
     *
     * @param  string  $role
     * @return bool
     */
    public function isAn($role)
    {
        $roles = func_get_args();

        $clipboard = $this->getClipboardInstance();

        return $clipboard->checkRole($this, $roles, 'or');
    }

    /**
     * Check if the model has any of the given roles.
     *
     * Alias for the "isAn" method.
     *
     * @param  string  $role
     * @return bool
     */
    public function isA($role)
    {
        return call_user_func_array([$this, 'isAn'], func_get_args());
    }

    /**
     * Check if the model has none of the given roles.
     *
     * @param  string  $role
     * @return bool
     */
    public function isNotAn($role)
    {
        $roles = func_get_args();

        $clipboard = $this->getClipboardInstance();

        return $clipboard->checkRole($this, $roles, 'not');
    }

    /**
     * Check if the model has none of the given roles.
     *
     * Alias for the "isNotAn" method.
     *
     * @param  string  $role
     * @return bool
     */
    public function isNotA($role)
    {
        return call_user_func_array([$this, 'isNotAn'], func_get_args());
    }

    /**
     * Check if the model has all of the given roles.
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
     * Constrain the given query by the provided role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role
     * @return void
     */
    public function scopeWhereIs($query, $role)
    {
        call_user_func_array(
            [new RolesQuery, 'constrainWhereIs'],
            func_get_args()
        );
    }

    /**
     * Constrain the given query by all provided roles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role
     * @return void
     */
    public function scopeWhereIsAll($query, $role)
    {
        call_user_func_array(
            [new RolesQuery, 'constrainWhereIsAll'],
            func_get_args()
        );
    }

    /**
     * Constrain the given query by the provided role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role
     * @return void
     */
    public function scopeWhereIsNot($query, $role)
    {
        call_user_func_array(
            [new RolesQuery, 'constrainWhereIsNot'],
            func_get_args()
        );
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
