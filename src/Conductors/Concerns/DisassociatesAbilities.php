<?php

namespace Silber\Bouncer\Conductors\Concerns;

use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Ability;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

trait DisassociatesAbilities
{
    use ConductsAbilities, FindsAndCreatesAbilities;

    /**
     * Remove the given ability from the model.
     *
     * @param  mixed  $abilities
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $entity
     * @param  array  $attributes
     * @return bool
     */
    public function to($abilities, $entity = null, array $attributes = [])
    {
        $authority = $this->getAuthority();

        if ($ids = $this->getAbilityIds($abilities, $entity, $attributes)) {
            $this->detachAbilities($authority, $ids);
        }

        return true;
    }

    /**
     * Detach the given IDs from the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $ids
     * @return void
     */
    protected function detachAbilities(Model $model, $ids)
    {
        $constraints = property_exists($this, 'constraints') ? $this->constraints : [];

        $this->getAbilitiesPivotQuery($model, $ids)
             ->where($this->constraints)
             ->delete();
    }

    /**
     * Get the base abilities pivot query.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $ids
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getAbilitiesPivotQuery(Model $model, $ids)
    {
        $relation = $model->abilities();

        $query = $relation->newPivotStatement();

        // We need to get the keys of both tables from the relation class.
        // These method names have changed in Laravel 5.4, so we'll now
        // first check which methods actually exist on the relation.
        if (method_exists($relation, 'getForeignKey')) {
            return $query->where($relation->getForeignKey(), $model->getKey())
                         ->whereIn($relation->getOtherKey(), $ids);
        }

        return $query->where($relation->getQualifiedForeignKeyName(), $model->getKey())
                     ->whereIn($relation->getQualifiedRelatedKeyName(), $ids);
    }

    /**
     * Get the authority from which to disassociate the abilities.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getAuthority()
    {
        if ($this->authority instanceof Model) {
            return $this->authority;
        }

        return Models::role()->where('name', $this->authority)->firstOrFail();
    }
}
