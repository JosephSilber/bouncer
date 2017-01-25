<?php

namespace Silber\Bouncer\Conductors\Concerns;

use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Ability;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

trait DisassociatesAbilities
{
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
        if ( ! $model = $this->getModel()) {
            return false;
        }

        if ($ids = $this->getAbilityIds($abilities, $entity, $attributes)) {
            $this->detachAbilities($model, $ids);
        }

        return true;
    }

    /**
     * Detach the given IDs from the model, with the given pivot constraints.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $ids
     * @param  array  $constraints
     * @return void
     */
    protected function detachAbilitiesWithPivotConstraints(Model $model, $ids, $constraints)
    {
        $this->getAbilitiesPivotQuery($model, $ids)->where($constraints)->delete();
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
     * Get the model from which to disassociate the abilities.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getModel()
    {
        if ($this->model instanceof Model) {
            return $this->model;
        }

        return Models::role()->where('name', $this->model)->first();
    }

    /**
     * Get the IDs of the provided abilities.
     *
     * @param  mixed  $abilities
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  array  $attributes
     * @return array
     */
    protected function getAbilityIds($abilities, $model, $attributes)
    {
        if ( ! is_null($model)) {
            return (array) $this->getModelAbilityId($abilities, $model, $attributes);
        }

        $abilities = is_array($abilities) ? $abilities : [$abilities];

        return array_merge(
            $this->filterNumericAbilities($abilities),
            $this->getAbilityIdsFromModels($abilities),
            $this->getAbilityIdsFromStrings($abilities)
        );
    }

    /**
     * Get the ability ID for the given model.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  array  $attributes
     * @return int|null
     */
    protected function getModelAbilityId($ability, $model, $attributes)
    {
        $onlyOwned = isset($attributes['only_owned']) ? $attributes['only_owned'] : false;

        return Models::ability()
                     ->byName($ability, true)
                     ->forModel($model, true)
                     ->where('only_owned', $onlyOwned)
                     ->value('id');
    }

    /**
     * Filter the provided abilities to the ones that are numeric.
     *
     * @param  array  $abilities
     * @return array
     */
    protected function filterNumericAbilities(array $abilities)
    {
        return array_filter($abilities, 'is_int');
    }

    /**
     * Get the Ability IDs from the models present in the given array.
     *
     * @param  array  $abilities
     * @return array
     */
    protected function getAbilityIdsFromModels(array $abilities)
    {
        $ids = [];

        foreach ($abilities as $ability) {
            if ($ability instanceof Ability) {
                $ids[] = $ability->getKey();
            }
        }

        return $ids;
    }

    /**
     * Get the ability IDs from the names present in the given array.
     *
     * @param  array  $abilities
     * @return array
     */
    protected function getAbilityIdsFromStrings(array $abilities)
    {
        $names = array_filter($abilities, 'is_string');

        if ( ! count($names)) {
            return [];
        }

        return Models::ability()
                     ->whereIn('name', $names)
                     ->get(['id'])
                     ->pluck('id')
                     ->all();
    }
}
