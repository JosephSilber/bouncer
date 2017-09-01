<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Helpers;
use Silber\Bouncer\Database\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class SyncsRolesAndAbilities
{
    use Concerns\FindsAndCreatesAbilities;

    /**
     * The authority for whom to sync roles/abilities.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $authority;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model  $authority
     */
    public function __construct(Model $authority)
    {
        $this->authority = $authority;
    }

    /**
     * Sync the provided roles to the authority.
     *
     * @param  iterable  $roles
     * @return void
     */
    public function roles($roles)
    {
        $this->authority->roles()->sync(Models::role()->getRoleKeys($roles), true);
    }

    /**
     * Sync the provided abilities to the authority.
     *
     * @param  iterable  $abilities
     * @return void
     */
    public function abilities($abilities)
    {
        $this->syncAbilities($abilities);
    }

    /**
     * Sync the provided forbidden abilities to the authority.
     *
     * @param  iterable  $abilities
     * @return void
     */
    public function forbiddenAbilities($abilities)
    {
        $this->syncAbilities($abilities, ['forbidden' => true]);
    }

    /**
     * Sync the given abilities for the authority.
     *
     * @param  iterable  $abilities
     * @param  array  $options
     * @return void
     */
    protected function syncAbilities($abilities, $options = ['forbidden' => false])
    {
        $abilityKeys = $this->getAbilityIds($abilities);

        $this->authority->abilities()
             ->whereNotIn($this->getAbilitiesQualifiedKeyName(), $abilityKeys)
             ->wherePivot('forbidden', $options['forbidden'])
             ->detach();

        if ($options['forbidden']) {
            (new ForbidsAbilities($this->authority))->to($abilityKeys);
        } else {
            (new GivesAbilities($this->authority))->to($abilityKeys);
        }
    }

    /**
     * Get the fully qualified column name for the abilities table's primary key.
     *
     * @return string
     */
    protected function getAbilitiesQualifiedKeyName()
    {
        $model = Models::ability();

        return $model->getTable().'.'.$model->getKeyName();
    }
}
