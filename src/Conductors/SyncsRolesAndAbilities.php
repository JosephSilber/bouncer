<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Helpers;
use Silber\Bouncer\Database\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class SyncsRolesAndAbilities
{
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
        $keyName = Models::ability()->getKeyName();

        $abilityKeys = $this->getAbilityIds($abilities, $keyName);

        $this->authority->abilities()
             ->wherePivot('forbidden', $options['forbidden'])
             ->detach();

        if ($options['forbidden']) {
            (new ForbidsAbilities($this->authority))->to($abilityKeys);
        } else {
            (new GivesAbilities($this->authority))->to($abilityKeys);
        }
    }

    /**
     * Get the IDs of the given abilities, creating new ones for the missing names.
     *
     * @param  iterable  $abilities
     * @param  string  $keyName
     * @return array
     */
    protected function getAbilityIds($abilities, $keyName)
    {
        $abilities = Helpers::groupModelsAndIdentifiersByType($abilities);

        $abilities['strings'] = $this->findAbilityKeysOrCreate(
            $abilities['strings'], $keyName
        );

        $abilities['models'] = Arr::pluck($abilities['models'], $keyName);

        return Arr::collapse($abilities);
    }

    /**
     * Find the IDs of the given ability names, creating the ones that are missing.
     *
     * @param  iterable  $names
     * @param  string  $keyName
     * @return array
     */
    protected function findAbilityKeysOrCreate($names, $keyName)
    {
        if (empty($names)) {
            return [];
        }

        $model = Models::ability();

        $existing = $model->simpleAbility()
                          ->whereIn('name', $names)
                          ->get([$keyName, 'name'])
                          ->pluck('name', $keyName);

        $creating = (new Collection($names))->diff($existing);

        $created = $creating->map(function ($name) use ($model) {
            return $model->create(compact('name'))->getKey();
        });

        return $created->merge($existing->keys())->all();
    }
}
