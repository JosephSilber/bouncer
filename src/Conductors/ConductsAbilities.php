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
     * @param  string|\Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    public function toManage($model)
    {
        return $this->to('*', $model);
    }

    /**
     * Allow/disallow the given ability on all models.
     *
     * @param  string  $ability
     * @return mixed
     */
    public function toAlways($ability)
    {
        return $this->to($ability, '*');
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
