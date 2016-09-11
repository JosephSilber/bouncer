<?php

namespace Silber\Bouncer\Database;

use Illuminate\Container\Container;

use Silber\Bouncer\Clipboard;
use Silber\Bouncer\Conductors\GivesAbility;
use Silber\Bouncer\Conductors\RemovesAbility;

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
     * Get all of the model's abilities.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities()
    {
        return $this->getClipboardInstance()->getAbilities($this);
    }

    /**
     * Give abilities to the model.
     *
     * @param  mixed  $ability
     * @param  mixed|null  $model
     * @return $this
     */
    public function allow($ability, $model = null)
    {
        (new GivesAbility($this))->to($ability, $model);

        return $this;
    }

    /**
     * Remove abilities from the model.
     *
     * @param  mixed  $ability
     * @param  mixed|null  $model
     * @return $this
     */
    public function disallow($ability, $model = null)
    {
        (new RemovesAbility($this))->to($ability, $model);

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
