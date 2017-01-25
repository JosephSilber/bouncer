<?php

namespace Silber\Bouncer\Conductors\Concerns;

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
     * Allow/disallow the given ability on all models.
     *
     * @param  array|string  $abilities
     * @param  array  $attributes
     * @return mixed
     */
    public function toAlways($abilities, array $attributes = [])
    {
        if ( ! is_array($abilities)) {
            return $this->to($abilities, '*', $attributes);
        }

        foreach ($abilities as $ability) {
            $this->to($ability, '*', $attributes);
        }
    }

    /**
     * Allow/disallow owning the given model.
     *
     * @param  string  $model
     * @param  array  $attributes
     * @return void
     */
    public function toOwn($model, array $attributes = [])
    {
        return $this->to('*', $model, $attributes + ['only_owned' => true]);
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
}
