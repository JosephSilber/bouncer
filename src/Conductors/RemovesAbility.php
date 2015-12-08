<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Ability;

use Illuminate\Database\Eloquent\Model;

class RemovesAbility
{
    /**
     * The model from which to remove a ability.
     *
     * @var \Illuminate\Database\Eloquent\Model|string
     */
    protected $model;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model|string  $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Remove the given ability from the model.
     *
     * @param  mixed  $abilities
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $entity
     * @return bool
     */
    public function to($abilities, $entity = null)
    {
        if ( ! $model = $this->getModel()) {
            return false;
        }

        if ($ids = $this->getAbilityIds($abilities, $entity)) {
            $model->abilities()->detach($ids);
        }

        return true;
    }

    /**
     * Get the model from which to remove the abilities.
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
     * @param  \ELoquent\Database\Eloquent\Model|string|null  $model
     * @return array|int
     */
    protected function getAbilityIds($abilities, $model)
    {
        if ( ! is_null($model)) {
            return $this->getModelAbilityId($abilities, $model);
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
     * @return int|null
     */
    protected function getModelAbilityId($ability, $model)
    {
        $model = $model instanceof Model ? $model : new $model;

        return Models::ability()->where('name', $ability)->forModel($model, true)->value('id');
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

        return Models::ability()->whereIn('name', $names)->lists('id')->all();
    }
}
