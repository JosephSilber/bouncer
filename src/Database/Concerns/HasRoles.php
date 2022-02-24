<?php

namespace Silber\Bouncer\Database\Concerns;

use Illuminate\Container\Container;

use Silber\Bouncer\Helpers;
use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Contracts\Clipboard;
use Silber\Bouncer\Conductors\AssignsRoles;
use Silber\Bouncer\Conductors\RemovesRoles;
use Silber\Bouncer\Database\Queries\Roles as RolesQuery;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasRoles
{
    /**
     * Boot the HasRoles trait.
     *
     * @return void
     */
    public static function bootHasRoles()
    {
        static::deleted(function ($model) {
            if (! Helpers::isSoftDeleting($model)) {
                $model->roles()->detach();
            }
        });
    }

    /**
     * The roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function roles(): MorphToMany
    {
        $relation = $this->morphToMany(
            Models::classname(Role::class),
            'entity',
            Models::table('assigned_roles')
        )->withPivot('scope');

        return Models::scope()->applyToRelation($relation);
    }

    /**
     * Get all of the model's assigned roles.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRoles()
    {
        return Container::getInstance()
            ->make(Clipboard::class)
            ->getRoles($this);
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
     * @param  string  ...$roles
     * @return bool
     */
    public function isAn(...$roles)
    {
        return Container::getInstance()
            ->make(Clipboard::class)
            ->checkRole($this, $roles, 'or');
    }

    /**
     * Check if the model has any of the given roles.
     *
     * Alias for the "isAn" method.
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function isA(...$roles)
    {
        return $this->isAn(...$roles);
    }

    /**
     * Check if the model has none of the given roles.
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function isNotAn(...$roles)
    {
        return Container::getInstance()
            ->make(Clipboard::class)
            ->checkRole($this, $roles, 'not');
    }

    /**
     * Check if the model has none of the given roles.
     *
     * Alias for the "isNotAn" method.
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function isNotA(...$roles)
    {
        return $this->isNotAn(...$roles);
    }

    /**
     * Check if the model has all of the given roles.
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function isAll(...$roles)
    {
        return Container::getInstance()
            ->make(Clipboard::class)
            ->checkRole($this, $roles, 'and');
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
        (new RolesQuery)->constrainWhereIs(...func_get_args());
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
        (new RolesQuery)->constrainWhereIsAll(...func_get_args());
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
        (new RolesQuery)->constrainWhereIsNot(...func_get_args());
    }
}
