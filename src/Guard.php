<?php

namespace Silber\Bouncer;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\HandlesAuthorization;

class Guard
{
    use HandlesAuthorization;

    /**
     * The guard's clipboard instance.
     *
     * @var \Silber\Bouncer\Contracts\Clipboard
     */
    protected $clipboard;

    /**
     * Determines where to run the clipboard's checks.
     *
     * Can be set to "before" or "after".
     *
     * @var string
     */
    protected $slot = 'before';

    /**
     * Create a new guard instance.
     *
     * @param \Silber\Bouncer\Contracts\Clipboard  $clipboard
     */
    public function __construct(Contracts\Clipboard $clipboard)
    {
        $this->clipboard = $clipboard;
    }

    /**
     * Get the clipboard instance.
     *
     * @return \Silber\Bouncer\Contracts\Clipboard
     */
    public function getClipboard()
    {
        return $this->clipboard;
    }

    /**
     * Set the clipboard instance.
     *
     * @param  \Silber\Bouncer\Contracts\Clipboard  $clipboard
     * @return $this
     */
    public function setClipboard(Contracts\Clipboard $clipboard)
    {
        $this->clipboard = $clipboard;

        return $this;
    }

    /**
     * Determine whether the clipboard used is a cached clipboard.
     *
     * @return bool
     */
    public function usesCachedClipboard()
    {
        return $this->clipboard instanceof Contracts\CachedClipboard;
    }

    /**
     * Set or get which slot to run the clipboard's checks.
     *
     * @param  string|null  $slot
     * @return $this|string
     */
    public function slot($slot = null)
    {
        if (is_null($slot)) {
            return $this->slot;
        }

        if (! in_array($slot, ['before', 'after'])) {
            throw new InvalidArgumentException(
                "{$slot} is an invalid gate slot"
            );
        }

        $this->slot = $slot;

        return $this;
    }

    /**
     * Register the clipboard at the given gate.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return $this
     */
    public function registerAt(Gate $gate)
    {
        $gate->before(function () {
            return call_user_func_array([
                $this, 'runBeforeCallback'
            ], func_get_args());
        });

        $gate->after(function () {
            return call_user_func_array([
                $this, 'runAfterCallback'
            ], func_get_args());
        });

        return $this;
    }

    /**
     * Run the gate's "before" callback.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  string  $ability
     * @param  mixed  $arguments
     * @param  mixed  $additional
     * @return bool|null
     */
    protected function runBeforeCallback($authority, $ability, $arguments = [], $additional = null)
    {
        if ($this->slot != 'before') {
            return;
        }

        list($model, $additional) = $this->parseGateBeforeArguments(
            $arguments, $additional
        );

        if (! is_null($additional)) {
            return;
        }

        return $this->checkAtClipboard($authority, $ability, $model);
    }

    /**
     * Run the gate's "before" callback.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  string  $ability
     * @param  mixed  $result
     * @param  array|null  $arguments
     * @return bool|null
     */
    protected function runAfterCallback($authority, $ability, $result, $arguments = null)
    {
        if (! is_null($result)) {
            return $result;
        }

        if ($this->slot != 'after') {
            return;
        }

        if (count($arguments) > 2) {
            return;
        }

        $model = isset($arguments[0]) ? $arguments[0] : null;

        return $this->checkAtClipboard($authority, $ability, $model);
    }

    /**
     * Parse the arguments we got from the gate.
     *
     * @param  mixed  $arguments
     * @param  mixed  $additional
     * @return array
     */
    protected function parseGateBeforeArguments($arguments, $additional)
    {
        // The way arguments are passed into the gate's before callback has changed in Laravel
        // in the middle of the 5.2 release. Before, arguments were spread out. Now they're
        // all supplied in a single array instead. We will normalize it into two values.
        if (! is_null($additional)) {
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
     * Run an auth check at the clipboard.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return mixed
     */
    protected function checkAtClipboard(Model $authority, $ability, $model)
    {
        if ($id = $this->clipboard->checkGetId($authority, $ability, $model)) {
            return $this->allow('Bouncer granted permission via ability #'.$id);
        }

        // If the response from "checkGetId" is "false", then this ability
        // has been explicity forbidden. We'll return false so the gate
        // doesn't run any further checks. Otherwise we return null.
        return $id;
    }
}
