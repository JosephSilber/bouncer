<?php

namespace Silber\Bouncer\Conductors\Concerns;

use Illuminate\Support\Arr;
use Silber\Bouncer\Database\Models;
use Illuminate\Database\Eloquent\Model;

trait AssociatesAbilities
{
    use ConductsAbilities, FindsAndCreatesAbilities;

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
     * @param  array  $abilityIds
     * @param  bool  $forbidden
     * @return array
     */
    protected function getAssociatedAbilityIds($authority, array $abilityIds, $forbidden)
    {
        if (is_null($authority)) {
            return $this->getAbilityIdsAssociatedWithEveryone($abilityIds, $forbidden);
        }

        $relation = $authority->abilities();

        $table = Models::table('abilities');

        $relation->whereIn("{$table}.id", $abilityIds)
                 ->wherePivot('forbidden', '=', $forbidden);

        Models::scope()->applyToRelation($relation);

        return $relation->get(["{$table}.id"])->pluck('id')->all();
    }

    /**
     * Get the IDs of the abilities associated with everyone.
     *
     * @param  array  $abilityIds
     * @param  bool  $forbidden
     * @return array
     */
    protected function getAbilityIdsAssociatedWithEveryone(array $abilityIds, $forbidden)
    {
        $query = Models::query('permissions')
            ->whereNull('entity_id')
            ->whereIn('ability_id', $abilityIds)
            ->where('forbidden', '=', $forbidden);

        Models::scope()->applyToRelationQuery($query, $query->from);

        return Arr::pluck($query->get(['ability_id']), 'ability_id');
    }
}
