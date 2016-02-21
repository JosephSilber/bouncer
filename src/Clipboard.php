<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\HandlesAuthorization;

class Clipboard
{
    use HandlesAuthorization;

    /**
     * Whether the bouncer is the exclusive authority on gate access.
     *
     * @var bool
     */
    protected $exclusive = false;

    /**
     * Register the clipboard at the given gate.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function registerAt(Gate $gate)
    {
        $gate->before(function ($user, $ability, $arguments, $additional = null) {
            list($model, $additional) = $this->parseGateArguments($arguments, $additional);

            if ( ! is_null($additional)) {
                return;
            }

            if ($id = $this->checkGetId($user, $ability, $model)) {
                return $this->allow('Bouncer granted permission via ability #'.$id);
            }

            if ($this->exclusive) {
                return false;
            }
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
     * Set whether the bouncer is the exclusive authority on gate access.
     *
     * @param  bool  $boolean
     * @return $this
     */
    public function setExclusivity($boolean)
    {
        $this->exclusive = $boolean;
    }

    /**
     * Determine if the given user has the given ability.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return bool
     */
    public function check(Model $user, $ability, $model = null)
    {
        return (bool) $this->checkGetId($user, $ability, $model);
    }

    /**
     * Determine if the given user has the given ability and return the ability ID.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return int|bool
     */
    protected function checkGetId(Model $user, $ability, $model = null)
    {
        $abilities = $this->getAbilities($user)->toBase()->lists('identifier', 'id');

        $requested = $this->compileAbilityIdentifiers($ability, $model);

        foreach ($abilities as $id => $ability) {
            if (in_array($ability, $requested)) {
                return $id;
            }
        }

        return false;
    }

    /**
     * Check if a user has the given roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  array|string  $roles
     * @param  string  $boolean
     * @return bool
     */
    public function checkRole(Model $user, $roles, $boolean = 'or')
    {
        $available = $this->getRoles($user)->intersect($roles);

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
     * @param  \Illuminate\Database\Eloquent