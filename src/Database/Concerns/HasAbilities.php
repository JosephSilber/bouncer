<?php

namespace Silber\Bouncer\Database\Concerns;

use Illuminate\Container\Container;

use Silber\Bouncer\Clipboard;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Conductors\GivesAbility;
use Silber\Bouncer\Conductors\ForbidsAbility;
use Silber\Bouncer\Conductors\RemovesAbility;
use Silber\Bouncer\Conductors\UnforbidsAbility;

trait HasAbilities
{
    /**
     * The abilities relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function abilities()
    {
        return $this->morphToMany(
            Models::classname(Ability::class),
            'entity',
            Models::table('permissions')
        );
    }

    /**
     * Get all of the model's allowed abilities.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities()
    {
        return $this->getClipboardInstance()->getAbilities($this);
    }

    /**
     * Get all of the model's allowed abilities.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getForbiddenAbilities()
    {
        return $this->getClipboardInstance()->getAbilities($this, false);
    }

    /**
     * Give an ability to the model.
     *
     * @param  mixed  $ability
     * @param  mixed|null  $model
     * @return \Silber\Bouncer\Conductors\GivesAbility|$this
     */
    public function allow($ability = null, $model = null)
    {
        if (is_null($ability)) {
            return new GivesAbility($this);
        }

        (new GivesAbility($this))->to($ability, $model);

        return $this;
    }

    /**
     * Remove an ability from the model.
     *
     * @param  mixed  $ability
     * @param  mixed|null  $model
     * @return \Silber\Bouncer\Conductors\RemovesAbility|$this
     */
    public function disallow($ability = null, $model = null)
    {
        if (is_null($ability)) {
            return new RemovesAbility($this);
        }

        (new RemovesAbility($this))->to($ability, $model);

        return $this;
    }

    /**
     * Forbid an ability to the model.
     *
     * @param  mixed  $ability
     * @param  mixed|null  $model
     * @return \Silber\Bouncer\Conductors\ForbidsAbility|$this
     */
    public function forbid($ability = null, $model = null)
    {
        if (is_null($ability)) {
            return new ForbidsAbility($this);
        }

        (new ForbidsAbility($this))->to($ability, $model);

        return $this;
    }

    /**
     * Remove ability forbiddal from the model.
     *
     * @param  mixed  $ability
     * @param  mixed|null  $model
     * @return \Silber\Bouncer\Conductors\UnforbidsAbility|$this
     */
    public function unforbid($ability = null, $model = null)
    {
        if (is_null($ability)) {
            return new UnforbidsAbility($this);
        }

        (new UnforbidsAbility($this))->to($ability, $model);

        return $this;
    }

    /**
     * Get an instance of the bouncer's clipboard.
     *
     * @return \Silber\Bouncer\Clipboard
     */
    protected function getClipboardInstance()
    {
        $container = Container::getInstance() ?: new Container;

        return $container->make(Clipboard::class);
    }
}
