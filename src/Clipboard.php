<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Contracts\Clipboard as ClipboardContract;
use Silber\Bouncer\Database\Queries\Abilities as AbilitiesQuery;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\HandlesAuthorization;

class Clipboard implements ClipboardContract
{
    use HandlesAuthorization;

    /**
     * Register the clipboard at the given gate.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function registerAt(Gate $gate)
    {
        $gate->before(function ($authority, $ability, $arguments = [], $additional = null) {
            list($model, $additional) = $this->parseGateArguments($arguments, $additional);

            if ( ! is_null($additional)) {
                return;
            }

            if ($id = $this->checkGetId($authority, $ability, $model)) {
                return $this->allow('Bouncer granted permission via ability #'.$id);
            }

            // If the response from "checkGetId" is "false", then this ability
            // has been explicity forbidden. We'll return false so the gate
            // doesn't run any further checks. Otherwise we return null.
            return $id;
        });
    }

    /**
     * Parse the arguments we got from the gate.
     *
     * @param  mixed  $arguments
     * @param  mixed  $additional
     * @return array
     */
    protected function parseGateArguments($arguments, $additional)
    {
        // The way arguments are passed into the gate's before callback has changed in Laravel
        // in the middle of the 5.2 release. Before, arguments were spread out. Now they're
        // all supplied in a single array instead. We will normalize it into two values.
        if ( ! is_null($additional)) {
            return [$arguments, $additional];
        }

        if (is_array($arguments)) {
            return [
                isset($arguments[0]) ? $arguments[0] : null,
                isset($arguments[1]) ? $arguments[1] : null,
            ];
        }

        return [$arguments, null];
    }

    /**
     * Determine if the given authority has the given ability.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return bool
     */
    public function check(Model $authority, $ability, $model = null)
    {
        return (bool) $this->checkGetId($authority, $ability, $model);
    }

    /**
     * Determine if the given authority has the given ability, and return the ability ID.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return int|bool|null
     */
    protected function checkGetId(Model $authority, $ability, $model = null)
    {
        $applicable = $this->compileAbilityIdentifiers($ability, $model);

        // We will first check if any of the applicable abilities have been forbidden.
        // If so, we'll return false right away, so as to not pass the check. Then,
        // we'll check if any of them have been allowed & return the matched ID.
        $forbiddenId = $this->findMatchingAbility(
            $this->getForbiddenAbilities($authority), $applicable, $model, $authority
        );

        if ($forbiddenId) {
            return false;
        }

        return $this->findMatchingAbility(
            $this->getAbilities($authority), $applicable, $model, $authority
        );
    }

    /**
     * Determine if any of the abilities can be matched against the provided applicable ones.
     *
     * @param  \Illuminate\Support\Collection  $abilities
     * @param  \Illuminate\Support\Collection  $applicable
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return int|null
     */
    protected function findMatchingAbility($abilities, $applicable, $model, $authority)
    {
        $abilities = $abilities->toBase()->pluck('identifier', 'id');

        if ($id = $this->getMatchedAbilityId($abilities, $applicable)) {
            return $id;
        }

        if ($model instanceof Model && Models::isOwnedBy($authority, $model)) {
            return $this->getMatchedAbilityId($abilities, $applicable->map(function ($identifier) {
                return $identifier.'-owned';
            }));
        }
    }

    /**
     * Get the ID of the ability that matches one of the applicable abilities.
     *
     * @param  \Illuminate\Support\Collection  $abilityMap
     * @param  \Illuminate\Support\Collection  $applicable
     * @return int|null
     */
    protected function getMatchedAbilityId(Collection $abilityMap, Collection $applicable)
    {
        foreach ($abilityMap as $id => $identifier) {
            if ($applicable->contains($identifier)) {
                return $id;
            }
        }
    }

    /**
     * Check if an authority has the given roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  array|string  $roles
     * @param  string  $boolean
     * @return bool
     */
    public function checkRole(Model $authority, $roles, $boolean = 'or')
    {
        $available = $this->getRoles($authority)
                          ->intersect(Models::role()->getRoleNames($roles));

        if ($boolean == 'or') {
            return $available->count() > 0;
        } elseif ($boolean === 'not') {
            return $available->count() === 0;
        }

        return $available->count() == count((array) $roles);
    }

    /**
     * Compile a list of ability identifiers that match the provided parameters.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return \Illuminate\Support\Collection
     */
    protected function compileAbilityIdentifiers($ability, $model)
    {
        $ability = strtolower($ability);

        if (is_null($model)) {
            return new Collection([$ability, '*-*', '*']);
        }

        return new Collection($this->compileModelAbilityIdentifiers($ability, $model));
    }

    /**
     * Compile a list of ability identifiers that match the given model.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return array
     */
    protected function compileModelAbilityIdentifiers($ability, $model)
    {
        if ($model === '*') {
            return ["{$ability}-*", "*-*"];
        }

        $model = $model instanceof Model ? $model : new $model;

        $type = strtolower($model->getMorphClass());

        $abilities = [
            "{$ability}-{$type}",
            "{$ability}-*",
            "*-{$type}",
            "*-*",
        ];

        if ($model->exists) {
            $abilities[] = "{$ability}-{$type}-{$model->getKey()}";
            $abilities[] = "*-{$type}-{$model->getKey()}";
        }

        return $abilities;
    }

    /**
     * Get the given authority's roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Illuminate\Support\Collection
     */
    public function getRoles(Model $authority)
    {
        $collection = $authority->roles()->get(['name'])->pluck('name');

        // In Laravel 5.1, "pluck" returns an Eloquent collection,
        // so we call "toBase" on it. In 5.2, "pluck" returns a
        // base instance, so there is no "toBase" available.
        if (method_exists($collection, 'toBase')) {
            $collection = $collection->toBase();
        }

        return $collection;
    }

    /**
     * Get a list of the authority's abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  bool  $allowed
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities(Model $authority, $allowed = true)
    {
        return (new AbilitiesQuery)->getForAuthority($authority, $allowed);
    }

    /**
     * Get a list of the authority's forbidden abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getForbiddenAbilities(Model $authority)
    {
        return $this->getAbilities($authority, false);
    }
}
