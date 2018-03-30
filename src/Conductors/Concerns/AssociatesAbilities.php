<?php

namespace Silber\Bouncer\Conductors\Concerns;

use Silber\Bouncer\Database\Models;
use Illuminate\Database\Eloquent\Model;

trait AssociatesAbilities
{
    use ConductsAbilities, FindsAndCreatesAbilities;

    /**
     * Get the authority, creating a role authority if necessary.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getAuthority()
    {
        if ($this->authority instanceof Model) {
            return $this->authority;
        }

        return Models::role()->firstOrCreate(['name' => $this->authority]);
    }

    /**
     * Get the IDs of the associated abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  array  $abilityIds
     * @param  bool $forbidden
     * @return array
     */
    protected function getAssociatedAbilityIds(Model $authority, array $abilityIds, $forbidden)
    {
        $relation = $authority->abilities();

        $relation->whereIn('id', $abilityIds)->wherePivot('forbidden', '=', $forbidden);

        Models::scope()->applyToRelation($relation);

        return $relation->get(['id'])->pluck('id')->all();
    }
}
