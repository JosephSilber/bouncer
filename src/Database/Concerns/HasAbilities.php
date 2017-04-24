<?php

namespace Silber\Bouncer\Database\Concerns;

use Illuminate\Container\Container;

use Silber\Bouncer\Clipboard;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Conductors\GivesAbilities;
use Silber\Bouncer\Conductors\ForbidsAbilities;
use Silber\Bouncer\Conductors\RemovesAbilities;
use Silber\Bouncer\Conductors\UnforbidsAbilities;

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
     * @return \Silber\Bouncer\Conductors\GivesAbilities|$this
     */
    public function allow($ability = null, $model = null)
    {
        if (is_null($ability)) {
            return new GivesAbilities($this);
        }

        (new GivesAbilities($this))->to($ability, $model);

        return $this;
    }

    /**
     * Remove an ability from the model.
     *
     * @param  mixed  $ability
     * @param  mixed|null  $model
     * @return \Silber\Bouncer\Conductors\RemovesAbilities|$this
     */
    public function disallow($ability = null, $model = null)
    {
        if (is_null($ability)) {
            return new RemovesAbilities($this);
        }

        (new RemovesAbilities($this))->to($ability, $model);

        return $this;
    }

    /**
     * Forbid an ability to the model.
     *
     * @param  mixed  $ability
     * @param  mixed|null  $model
     * @return \Silber\Bouncer\Conductors\ForbidsAbilities|$this
     */
    public function forbid($ability = null, $model = null)
    {
        if (is_null($ability)) {
            return new ForbidsAbilities($this);
        }

        (new ForbidsAbilities($this))->to($ability, $model);

        return $this;
    }

    /**
     * Remove ability forbiddal from the model.
     *
     * @param  mixed  $ability
     * @param  mixed|null  $model
     * @return \Silber\Bouncer\Conductors\UnforbidsAbilities|$this
     */
    public function unforbid($ability = null, $model = null)
    {
        if (is_null($ability)) {
            return new UnforbidsAbilities($this);
        }

        (new UnforbidsAbilities($this))->to($ability, $model);

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
