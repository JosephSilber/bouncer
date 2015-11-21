<?php

namespace Silber\Bouncer\Conductors;

use Exception;
use InvalidArgumentException;
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
     * @var string
     */
    private $abilityModelClass;
    /**
     * @var string
     */
    private $roleModelClass;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model|string $model
     * @param string $roleModelClass
     * @param string $abilityModelClass
     */
    public function __construct($model, $roleModelClass = 'Silber\Bouncer\Database\Role', $abilityModelClass = 'Silber\Bouncer\Database\Ability')
    {
        $this->model = $model;
        $this->abilityModelClass = $abilityModelClass;
        $this->roleModelClass = $roleModelClass;
    }

    /**
     * Give the abilities to the model.
     *
     * @param  mixed  $abilities
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return bool
     */
    public function to($abilities, $model = null)
    {
        $ids = $this->getAbilityIds($abilities, $model);

        $this->giveAbilities($ids, $this->getModel());

        return true;
    }

    /**
     * Give abilities to the given model.
     *
     * @param  array  $ids
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    protected function giveAbilities(array $ids, Model $model)
    {
        $existing = $model->abilities()->whereIn('id', $ids)->lists('id')->all();

        $ids = array_diff($ids, $existing);

        $model->abilities()->attach($ids);
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

        return call_user_func($this->roleModelClass."::firstOrCreate", ['name' => $this->model]);
    }

    /**
     * Get the IDs of the provided abilities.
     *
     * @param  \Silber\Bouncer\Database\Ability|array|int  $abilities
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return array
     */
    protected function getAbilityIds($abilities, $model)
    {
        if ($abilities instanceof Ability) {
            return [$abilities->getKey()];
        }

        if ( ! is_null($model)) {
            return [$this->getModelAbility($abilities, $model)->getKey()];
        }

        return $this->abilitiesByName($abilities)->pluck('id')->all();
    }

    /**
     * Get an ability for the given entity.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string  $entity
     * @return \Silber\Bouncer\Database\Ability
     */
    protected function getModelAbility($ability, $entity)
    {
        $entity = $this->getEntityInstance($entity);

        $model = call_user_func($this->abilityModelClass."::where", 'name', $ability)->forModel($entity)->first();

        return $model ?: call_user_func($this->abilityModelClass."::createForModel", $entity, $ability);
    }

    /**
     * Get an instance of the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getEntityInstance($model)
    {
        if ( ! $model instanceof Model) {
            return new $model;
        }

        // Creating an ability for a model that doesn't exist gives the user the
        // ability on all instances of that model. If the developer passed in
        // a model instance that does not exist, it is probably a mistake.
        if ( ! $model->exists) {
            throw new InvalidArgumentException(
                'The model does not exist. To allow access to all models, use the class name instead'
            );
        }

        return $model;
    }

    /**
     * Get or create abilities by their name.
     *
     * @param  array|string  $ability
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function abilitiesByName($ability)
    {
        $abilities = array_unique(is_array($ability) ? $ability : [$ability]);

        /** @var \Illuminate\Database\Eloquent\Collection $models */
        $models = call_user_func($this->abilityModelClass."::simpleAbility")->whereIn('name', $abilities)->get();

        $created = $this->createMissingAbilities($models, $abilities);

        return $models->merge($created);
    }

    /**
     * Create abilities whose name is not in the given list.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @param  array  $abilities
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function createMissingAbilities(Collection $models, array $abilities)
    {
        $missing = array_diff($abilities, $models->pluck('name')->all());

        $created = [];

        foreach ($missing as $ability) {
            $created[] = call_user_func($this->abilityModelClass."::create", ['name' => $ability]);
        }

        return $created;
    }
}
