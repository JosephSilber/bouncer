<?php

namespace Silber\Bouncer\Database\Concerns;

use Illuminate\Container\Container;
use Silber\Bouncer\Contracts\Clipboard;

trait Authorizable
{
    /**
     * Determine if the authority has a given ability.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return bool
     */
    public function can($ability, $model = null)
    {
        return Container::getInstance()
            ->make(Clipboard::class)
            ->check($this, $ability, $model);
    }

    /**
     * Determine if the authority does not have a given ability.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return bool
     */
    public function cant($ability, $model = null)
    {
        return ! $this->can($ability, $model);
    }

    /**
     * Determine if the authority does not have a given ability.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return bool
     */
    public function cannot($ability, $model = null)
    {
        return $this->cant($ability, $model);
    }
}
