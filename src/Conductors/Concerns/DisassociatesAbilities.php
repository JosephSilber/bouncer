<?php

namespace Silber\Bouncer\Conductors\Concerns;

use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Ability;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait DisassociatesAbilities
{
    use ConductsAbilities, FindsAndCreatesAbilities;

    /**
     * Remove the given ability from the model.
     *
     * @param  mixed  $abilities
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $entity
     * @param  array  $attributes
     * @return bool|\Silber\Bouncer\Conductors\Lazy\ConductsAbilities
     */
    public function to($abilities, $entity = null, array $attributes = [])
    {
        if (call_user_func_array([$this, 'shouldConductLazy'], func_get_args())) {
            return $this->conductLazy($abilities);
        }

        if ($ids = $this->getAbilityIds($abilities, $entity, $attributes)) {
            $this->disassociateAbilities($this->getAuthority(), $ids);
        }

        return true;
    }

    /**
     * Detach the given IDs from the authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $authority
     * @param  array  $ids
     * @return void
     */
    protected function disassociateAbilities($authority, array $ids)
    {
        if (is_null($authority)) {
            $this->disassociateEveryone($ids);
        } else {
            $this->disassociateAuthority($authority, $ids);
        }
    }

    /**
     * Disassociate the authority from the abilities with the given IDs.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  array  $ids
     * @return void
     */
    protected function disassociateAuthority(Model $authority, array $ids)
    {
        $this->getAbilitiesPivotQuery($authority, $ids)
             ->where($this->constraints())
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

        list($foreignKeyName, $relatedKeyName) = $this->getRelationKeyNames($relation);

        $query = $relation
            ->newPivotStatement()
            ->where($foreignKeyName, $model->getKey())
            ->where('entity_type', $model->getMorphClass())
            ->whereIn($relatedKeyName, $ids);

        return Models::scope()->applyToRelationQuery(
            $query, $relation->getTable()
        );
    }

    /**
     * Get the two primary key names from the relation.
     *
     * @param  Illuminate\Database\Eloquent\Relations\MorphToMany  $relation
     * @return array
     */
    protected function getRelationKeyNames(MorphToMany $relation)
    {
        // We need to get the keys of both tables from the relation class.
        // The method names have changed in Laravel 5.4 & again in 5.5,
        // so we will first check which methods are available to us.
        if (method_exists($relation, 'getForeignKey')) {
            return [
                $relation->getForeignKey(),
                $relation->getOtherKey(),
            ];
        }

        if (method_exists($relation, 'getQualifiedForeignKeyName')) {
            return [
                $relation->getQualifiedForeignKeyName(),
                $relation->getQualifiedRelatedKeyName(),
            ];
        }

        return [
            $relation->getQualifiedForeignPivotKeyName(),
            $relation->getQualifiedRelatedPivotKeyName(),
        ];
    }

    /**
     * Disassociate everyone from the abilities with the given IDs.
     *
     * @param  array  $ids
     * @return void
     */
    protected function disassociateEveryone(array $ids)
    {
        $query = Models::query('permissions')
            ->whereNull('entity_id')
            ->where($this->constraints())
            ->whereIn('ability_id', $ids);

        Models::scope()->applyToRelationQuery($query, $query->from);

        $query->delete();
    }

    /**
     * Get the authority from which to disassociate the abilities.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getAuthority()
    {
        if (is_null($this->authority)) {
            return null;
        }

        if ($this->authority instanceof Model) {
            return $this->authority;
        }

        return Models::role()->where('name', $this->authority)->firstOrFail();
    }

    /**
     * Get the additional constraints for the detaching query.
     *
     * @return array
     */
    protected function constraints()
    {
        return property_exists($this, 'constraints') ? $this->constraints : [];
    }
}
