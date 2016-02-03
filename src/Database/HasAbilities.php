<?php

namespace Silber\Bouncer\Database;

use Illuminate\Container\Container;

use Silber\Bouncer\Clipboard;
use Silber\Bouncer\Conductors\GivesAbility;
use Silber\Bouncer\Conductors\RemovesAbility;
use Silber\Bouncer\Database\Constraints\Abilities as AbilitiesConstraint;

trait HasAbilities
{
    /**
     * The abilities relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function abilities()
    {
        return $this->belongsToMany(
            Models::classname(Ability::class),
            Models::table('user_abilities'),
            'user_id'
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
     * Constrain the given query by the provided ability.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return void
     */
    public function scopeWhereCan($query, $ability, $model = null)
    {
        (new AbilitiesConstraint)->constrainUsers($query, $ability, $model);
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
