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
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function constrain($query, $ability, $model = null)
    {
        return $query->where(function ($query) use ($ability, $model) {
            $query->whereHas('abilities', $this->getAbilityConstraint($ability, $model));

            if (method_exists($query->getModel(), 'roles')) {
                $query->orWhereHas('roles', $this->getRoleConstraint($ability, $model));
            }
        });
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
