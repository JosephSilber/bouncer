<?php

namespace Silber\Bouncer\Conductors\Concerns;

use Silber\Bouncer\Helpers;
use Illuminate\Support\Collection;
use Silber\Bouncer\Conductors\Lazy;

trait ConductsAbilities
{
    /**
     * Allow/disallow all abilities on everything.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function everything(array $attributes = [])
    {
        return $this->to('*', '*', $attributes);
    }

    /**
     * Allow/disallow all abilities on the given model.
     *
     * @param  string|array|\Illuminate\Database\Eloquent\Model  $models
     * @param  array  $attributes
     * @return void
     */
    public function toManage($models, array $attributes = [])
    {
        if (is_array($models)) {
            foreach ($models as $model) {
                $this->to('*', $model, $attributes);
            }
        } else {
            $this->to('*', $models, $attributes);
        }
    }

    /**
     * Allow/disallow owning the given model.
     *
     * @param  string|object  $model
     * @param  array  $attributes
     * @return void
     */
    public function toOwn($model, array $attributes = [])
    {
        return new Lazy\HandlesOwnership($this, $model, $attributes);
    }

    /**
     * Allow/disallow owning all models.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function toOwnEverything(array $attributes = [])
    {
        return $this->toOwn('*', $attributes);
    }

    /**
     * Determines whether a call to "to" with the given parameters should be conducted lazily.
     *
     * @param  mixed  $abilities
     * @param  mixed  $model
     * @return bool
     */
    protected function shouldConductLazy($abilities)
    {
        // We'll only create a lazy conductor if we got a single
        // param, and that single param is either a string or
        // a numerically-indexed array (of simple strings).
        if (func_num_args() > 1) {
            return false;
        }

        if (is_string($abilities)) {
            return true;
        }

        if (! is_array($abilities) || ! Helpers::isIndexedArray($abilities)) {
            return false;
        }

        // In an ideal world, we'd be using $collection->every('is_string').
        // Since we also support older versions of Laravel, we'll need to
        // use "array_filter" with a double count. Such is legacy life.
        return count(array_filter($abilities, 'is_string')) == count($abilities);
    }

    /**
     * Create a lazy abilities conductor.
     *
     * @param  string|string[]  $ablities
     * @return \Silber\Bouncer\Conductors\Lazy\ConductsAbilities
     */
    protected function conductLazy($abilities)
    {
        return new Lazy\ConductsAbilities($this, $abilities);
    }
}
