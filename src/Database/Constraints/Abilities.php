<?php

namespace Silber\Bouncer\Database\Constraints;

use Silber\Bouncer\Database\Models;

class Abilities
{
    /**
     * Constrain the given users query by the provided ability.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function constrainUsers($query, $ability, $model = null)
    {
        return $query->where(function ($query) use ($ability, $model) {
            $query->whereHas('abilities', $this->getAbilityConstraint($ability, $model));

            $query->orWhereHas('roles', $this->getRoleConstraint($ability, $model));
        });
    }

    /**
     * Constrain the given roles query by the provided ability.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function constrainRoles($query, $ability, $model = null)
    {
        $constraint = $this->getAbilityConstraint($ability, $model);

        return $query->whereHas('abilities', $constraint);
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
            $table = Models::table('abilities');

            $query->where("{$table}.name", $ability);

            if ( ! is_null($model)) {
                $query->forModel($model);
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
