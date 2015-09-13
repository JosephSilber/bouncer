<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Role;
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
     * @param  mixed  $Abilities
     * @return bool
     */
    public function to($abilities)
    {
        if ( ! $model = $this->getModel()) {
            return false;
        }

        $model->abilities()->detach($this->getAbilityIds($abilities));

        return true;
    }

    /**
     * Get the model.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getModel()
    {
        if ($this->model instanceof Model) {
            return $this->model;
        }

        return Role::where('title', $this->model)->first();
    }

    /**
     * Get the IDs of the provided abilities.
     *
     * @return array
     */
    protected function getAbilityIds($abilities)
    {
        $abilities = is_array($abilities) ? $abilities : [$abilities];

        return array_merge(
            $this->filterNumericAbilities($abilities),
            $this->getAbilityIdsFromModels($abilities),
            $this->getAbilityIdsFromStrings($abilities)
        );
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
     * Get the ability IDs from the titles present in the given array.
     *
     * @param  array  $abilities
     * @return array
     */
    protected function getAbilityIdsFromStrings(array $abilities)
    {
        $titles = array_filter($abilities, 'is_string');

        if ( ! count($titles)) {
            return [];
        }

        return Ability::whereIn('title', $titles)->lists('id')->all();
    }
}
