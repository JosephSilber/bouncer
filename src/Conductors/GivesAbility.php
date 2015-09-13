<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class GivesAbility
{
    /**
     * The model to be given abilities.
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
     * Give the abilities to the model.
     *
     * @param  mixed  $abilities
     * @return bool
     */
    public function to($abilities)
    {
        $ids = $this->getAbilityIds($abilities);

        $this->getModel()->abilities()->attach($ids);

        return true;
    }

    /**
     * Get the model or create a role.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getModel()
    {
        if ($this->model instanceof Model) {
            return $this->model;
        }

        return Role::firstOrCreate(['title' => $this->model]);
    }

    /**
     * Get the IDs of the provided abilities.
     *
     * @param  \Silber\Bouncer\Database\Ability|array|int  $abilities
     * @return array
     */
    protected function getAbilityIds($abilities)
    {
        if ($abilities instanceof Ability) {
            return [$abilities->getKey()];
        }

        return $this->AbilitiesByTitle($abilities)->pluck('id')->all();
    }

    /**
     * Get or create abilities by their title.
     *
     * @param  array|string  $Ability
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function AbilitiesByTitle($ability)
    {
        $abilities = array_unique(is_array($ability) ? $ability : [$ability]);

        $models = Ability::whereIn('title', $abilities)->get();

        $created = $this->createMissingAbilities($models, $abilities);

        return $models->merge($created);
    }

    /**
     * Create abilities whose title is not in the given list.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @param  array  $abilities
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function createMissingAbilities(Collection $models, array $abilities)
    {
        $missing = array_diff($abilities, $models->pluck('title')->all());

        $created = [];

        foreach ($missing as $ability) {
            $created[] = Ability::create(['title' => $ability]);
        }

        return $created;
    }
}
