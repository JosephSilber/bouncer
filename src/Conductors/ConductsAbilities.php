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
}
