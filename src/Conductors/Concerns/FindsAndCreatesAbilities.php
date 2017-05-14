<?php

namespace Silber\Bouncer\Conductors\Concerns;

use Silber\Bouncer\Helpers;
use Silber\Bouncer\Database\Models;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

trait FindsAndCreatesAbilities
{
    /**
     * Get the IDs of the provided abilities.
     *
     * @param  \Illuminate\Database\Eloquent\model|array|int  $abilities
     * @param  \Illuminate\Database\Eloquent\Model|string|array|null  $model
     * @param  array  $attributes
     * @return array
     */
    protected function getAbilityIds($abilities, $model, array $attributes)
    {
        if ($abilities instanceof Model) {
            return [$abilities->getKey()];
        }

        if ( ! is_null($model)) {
            return $this->getModelAbilityKeys($abilities, $model, $attributes);
        }

        if (Helpers::isAssociativeArray($abilities)) {
            return $this->getAbilityIdsFromMap($abilities, $attributes);
        }

        return $this->abilitiesByName($abilities, $attributes)->pluck('id')->all();
    }

    /**
     * Get the ability IDs for the given map.
     *
     * The map should use the ['ability-name' => Entity::class] format.
     *
     * @param  array  $map
     * @param  array  $attributes
     * @return array
     */
    protected function getAbilityIdsFromMap(array $map, array $attributes)
    {
        return (new Collection($map))
            ->map(function ($entity, $ability) use ($attributes) {
                return $this->getAbilityIds($ability, $entity, $attributes);
            })->flatten(1)->all();
    }

    /**
     * Get the abilities for the given model ability descriptors.
     *
     * @param  array|string  $abilities
     * @param  \Illuminate\Database\Eloquent\Model|string|array  $model
     * @param  array  $attributes
     * @return array
     */
    protected function getModelAbilityKeys($abilities, $model, array $attributes)
    {
        $abilities = Collection::make(is_array($abilities) ? $abilities : [$abilities]);

        $models = Collection::make(is_array($model) ? $model : [$model]);

        return $abilities->map(function ($ability) use ($models, $attributes) {
            return $models->map(function ($model) use ($ability, $attributes) {
                return $this->getModelAbility($ability, $model, $attributes)->getKey();
            });
        })->collapse()->all();
    }

    /**
     * Get an ability for the given entity.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string  $entity
     * @param  array  $attributes
     * @return \Silber\Bouncer\Database\Ability
     */
    protected function getModelAbility($ability, $entity, array $attributes)
    {
        $entity = $this->getEntityInstance($entity);

        $existing = $this->findAbility($ability, $entity, $attributes);

        return $existing ?: $this->createAbility($ability, $entity, $attributes);
    }

    /**
     * Find the ability for the given entity.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string  $entity
     * @param  array  $attributes
     * @return \Silber\Bouncer\Database\Ability|null
     */
    protected function findAbility($ability, $entity, $attributes)
    {
        $onlyOwned = isset($attributes['only_owned']) ? $attributes['only_owned'] : false;

        return Models::ability()
                     ->where('name', $ability)
                     ->forModel($entity, true)
                     ->where('only_owned', $onlyOwned)
                     ->first();
    }

    /**
     * Create an ability for the given entity.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string  $entity
     * @param  array  $attributes
     * @return \Silber\Bouncer\Database\Ability
     */
    protected function createAbility($ability, $entity, $attributes)
    {
        return Models::ability()->createForModel($entity, $attributes + [
            'name' => $ability,
        ]);
    }

    /**
     * Get an instance of the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return \Illuminate\Database\Eloquent\Model|string
     */
    protected function getEntityInstance($model)
    {
        if ($model == '*') {
            return '*';
        }

        if ( ! $model instanceof Model) {
            return new $model;
        }

        // Creating an ability for a non-existent model gives the authority that
        // ability on all instances of that model. If the developer passed in
        // a model instance that does not exist, it is probably a mistake.
        if ( ! $model->exists) {
            throw new InvalidArgumentException(
                'The model does not exist. To edit access to all models, use the class name instead'
            );
        }

        return $model;
    }

    /**
     * Get or create abilities by their name.
     *
     * @param  array|string  $ability
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function abilitiesByName($ability, $attributes = [])
    {
        $abilities = array_unique(is_array($ability) ? $ability : [$ability]);

        $existing = Models::ability()->simpleAbility()->whereIn('name', $abilities)->get();

        return $existing->merge($this->createMissingAbilities(
            $existing, $abilities, $attributes
        ));
    }

    /**
     * Create the non-existant abilities by name.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $existing
     * @param  string[]  $abilities
     * @param  array  $attributes
     * @return array
     */
    protected function createMissingAbilities($existing, array $abilities, $attributes = [])
    {
        $missing = array_diff($abilities, $existing->pluck('name')->all());

        return array_map(function ($ability) use ($attributes) {
            return Models::ability()->create($attributes + ['name' => $ability]);
        }, $missing);
    }
}
