<?php

namespace Silber\Bouncer\Conductors;

trait ConductsAbilities
{
    /**
     * Allow/disallow all abilities on everything.
     *
     * @return mixed
     */
    public function everything()
    {
        return $this->to('*', '*');
    }

    /**
     * Allow/disallow all abilities on the given model.
     *
     * @param  string|array|\Illuminate\Database\Eloquent\Model  $models
     * @return void
     */
    public function toManage($models)
    {
        if (is_array($models)) {
            foreach ($models as $model) {
                $this->to('*', $model);
            }
        } else {
            $this->to('*', $models);
        }
    }

    /**
     * Allow/disallow the given ability on all models.
     *
     * @param  array|string  $abilities
     * @return mixed
     */
    public function toAlways($abilities)
    {
        if (is_array($abilities)) {
            foreach ($abilities as $ability) {
                $this->to($ability, '*');
            }
        } else {
            $this->to($abilities, '*');
        }
    }

    /**
     * Allow/disallow owning the given model.
     *
     * @param  string  $model
     * @param  string|array  $abilities
     * @return void
     */
    public function toOwn($model, $abilities = '*')
    {
        if (is_string($abilities)) {
            $this->to($abilities, $model, true);
        } else {
            foreach ($abilities as $ability) {
                $this->to($ability, $model, true);
            }
        }
    }

    /**
     * Allow/disallow owning all models.
     *
     * @param  string|array  $abilities
     * @return mixed
     */
    public function toOwnEverything($abilities = '*')
    {
        return $this->toOwn('*', $abilities);
    }
}
