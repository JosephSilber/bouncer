<?php

namespace Silber\Bouncer\Conductors\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Silber\Bouncer\Database\Models;

trait AssociatesAbilities
{
    use ConductsAbilities, FindsAndCreatesAbilities;

    /**
     * Associate the abilities with the authority.
     *
     * @param  \Illuminate\Database\Eloquent\model|array|int|string  $abilities
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return \Silber\Bouncer\Conductors\Lazy\ConductsAbilities|null
     */
    public function to($abilities, $model = null, array $attributes = [])
    {
        if ($this->shouldConductLazy(...func_get_args())) {
            return $this->conductLazy($abilities);
        }

        $ids = $this->getAbilityIds($abilities, $model, $attributes);

        $this->associateAbilities($ids, $this->getAuthority());
    }

    /**
     * Get the authority, creating a role authority if necessary.
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

        return Models::role()->firstOrCreate(['name' => $this->authority]);
    }

    /**
     * Get the IDs of the associated abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $authority
     * @return array
     */
    protected function getAssociatedAbilityIds($authority, array $abilityIds)
    {
        if (is_null($authority)) {
            return $this->getAbilityIdsAssociatedWithEveryone($abilityIds);
        }

        $relation = $authority->abilities();

        $table = Models::table('abilities');

        $relation->whereIn("{$table}.id", $abilityIds)
            ->wherePivot('forbidden', '=', $this->forbidding);

        Models::scope()->applyToRelation($relation);

        return $relation->get(["{$table}.id"])->pluck('id')->all();
    }

    /**
     * Get the IDs of the abilities associated with everyone.
     *
     * @return array
     */
    protected function getAbilityIdsAssociatedWithEveryone(array $abilityIds)
    {
        $query = Models::query('permissions')
            ->whereNull('entity_id')
            ->whereIn('ability_id', $abilityIds)
            ->where('forbidden', '=', $this->forbidding);

        Models::scope()->applyToRelationQuery($query, $query->from);

        return Arr::pluck($query->get(['ability_id']), 'ability_id');
    }

    /**
     * Associate the given ability IDs on the permissions table.
     *
     * @return void
     */
    protected function associateAbilities(array $ids, ?Model $authority = null)
    {
        $ids = array_diff($ids, $this->getAssociatedAbilityIds($authority, $ids, false));

        if (is_null($authority)) {
            $this->associateAbilitiesToEveryone($ids);
        } else {
            $this->associateAbilitiesToAuthority($ids, $authority);
        }
    }

    /**
     * Associate these abilities with the given authority.
     *
     * @return void
     */
    protected function associateAbilitiesToAuthority(array $ids, Model $authority)
    {
        $attributes = Models::scope()->getAttachAttributes(get_class($authority));

        $authority
            ->abilities()
            ->attach($ids, ['forbidden' => $this->forbidding] + $attributes);
    }

    /**
     * Associate these abilities with everyone.
     *
     * @return void
     */
    protected function associateAbilitiesToEveryone(array $ids)
    {
        $attributes = ['forbidden' => $this->forbidding];

        $attributes += Models::scope()->getAttachAttributes();

        $records = array_map(function ($id) use ($attributes) {
            return ['ability_id' => $id] + $attributes;
        }, $ids);

        Models::query('permissions')->insert($records);
    }
}
