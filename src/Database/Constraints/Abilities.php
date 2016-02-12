<?php

namespace Silber\Bouncer\Database\Constraints;

use Silber\Bouncer\Database\Models;

class Abilities
{
    /**
     * Constrain the given query by the provided ability.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  bool  $has
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function constrain($query, $ability, $model = null, $has = true)
    {
        return $query->where(function ($query) use ($ability, $model, $has) {
            $this->constrainDirect($query, $ability, $model, $has);

            if (method_exists($query->getModel(), 'roles')) {
                $this->constrainThroughRoles($query, $ability, $model, $has);
            }
        });
    }

    /**
     * Constrain the given query by whether it has the provided ability directly.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  bool  $has
     * @return void
     */
    protected function constrainDirect($query, $ability, $model = null, $has = true)
    {
        $constraint = $this->getAbilityConstraint($ability, $model);

        $method = $has ? 'whereHas' : 'whereDoesntHave';

        $query->{$method}('abilities', $constraint);
    }

    /**
     * Constrain the given query by whether it has the provided ability through a role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  bool  $has
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function constrainThroughRoles($query, $ability, $model = null, $has = true)
    {
        $constraint = $this->getAbilityConstraint($ability, $model);

        $method = $has ? 'orWhereHas' : 'whereDoesntHave';

        $query->{$method}('roles', $this->getRoleConstraint($ability, $model));
    }

    /**
     * Get the callback to constrain an abilities query to the given ability.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return \Closure
     */
    protected function getAbilityConstraint($ability, $model)
    {
        return function ($query) use ($ability, $model) {
            $query->where($this->getAbilityNameConstraint($ability, $model));

            if ( ! is_null($model)) {
                $query->forModel($model);
            }
        };
    }

    /**
     * Get the callback for the name part of an ability constraint.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return \Closure
     */
    protected function getAbilityNameConstraint($ability, $model)
    {
        return function ($query) use ($ability, $model) {
            $abilities = Models::table('abilities');

            $query->whereIn("{$abilities}.name", [$ability, '*']);

            if (is_null($model)) {
                $query->whereNull("{$abilities}.entity_id")
                      ->whereNull("{$abilities}.entity_type");
            }
        };
    }

    /**
     * Get the callback to constrain a roles query to the given ability.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return \Closure
     */
    protected function getRoleConstraint($ability, $model)
    {
        return function ($query) use ($ability, $model) {
            $query->whereHas('abilities', $this->getAbilityConstraint($ability, $model));
        };
    }
}
