<?php

namespace Silber\Bouncer\Database\Titles;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\model;

class AbilityTitle extends Title
{
    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\model  $ability
     */
    public function __construct(Model $ability)
    {
        if ($this->isWildcardAbility($ability)) {
            $this->title = $this->getWildcardAbilityTitle($ability);
        } else if ($this->isRestrictedWildcardAbility($ability)) {
            $this->title = 'All simple abilities';
        } else if ($this->isSimpleAbility($ability)) {
            $this->title = $this->humanize($ability->name);
        } else if ($this->isRestrictedOwnershipAbility($ability)) {
            $this->title = $this->humanize($ability->name.' everything owned');
        } else if ($this->isGeneralManagementAbility($ability)) {
            $this->title = $this->getBlanketModelAbilityTitle($ability);
        } else if ($this->isBlanketModelAbility($ability)) {
            $this->title = $this->getBlanketModelAbilityTitle($ability, $ability->name);
        } else if ($this->isSpecificModelAbility($ability)) {
            $this->title = $this->getSpecificModelAbilityTitle($ability);
        } else if ($this->isGlobalActionAbility($ability)) {
            $this->title = $this->humanize($ability->name.' everything');
        }
    }

    /**
     * Determines if the given ability allows all abilities.
     *
     * @param  \Illuminate\Database\Eloquent\model  $ability
     * @return bool
     */
    protected function isWildcardAbility(Model $ability)
    {
        return $ability->name === '*' && $ability->entity_type === '*';
    }

    /**
     * Determines if the given ability allows all simple abilities.
     *
     * @param  \Illuminate\Database\Eloquent\model  $ability
     * @return bool
     */
    protected function isRestrictedWildcardAbility(Model $ability)
    {
        return $ability->name === '*' && is_null($ability->entity_type);
    }

    /**
     * Determines if the given ability is a simple (non model) ability.
     *
     * @param  \Illuminate\Database\Eloquent\model  $ability
     * @return bool
     */
    protected function isSimpleAbility(Model $ability)
    {
        return is_null($ability->entity_type);
    }

    /**
     * Determines whether the given ability is a global
     * ownership ability restricted to a specific action.
     *
     * @param  \Illuminate\Database\Eloquent\model  $ability
     * @return bool
     */
    protected function isRestrictedOwnershipAbility(Model $ability)
    {
        return $ability->only_owned && $ability->name !== '*' && $ability->entity_type === '*';
    }

    /**
     * Determines whether the given ability is for managing all models of a given type.
     *
     * @param  \Illuminate\Database\Eloquent\model  $ability
     * @return bool
     */
    protected function isGeneralManagementAbility(Model $ability)
    {
        return $ability->name === '*'
            && $ability->entity_type !== '*'
            && ! is_null($ability->entity_type)
            && is_null($ability->entity_id);
    }

    /**
     * Determines whether the given ability is for an action on all models of a given type.
     *
     * @param  \Illuminate\Database\Eloquent\model  $ability
     * @return bool
     */
    protected function isBlanketModelAbility(Model $ability)
    {
        return $ability->name !== '*'
            && $ability->entity_type !== '*'
            && ! is_null($ability->entity_type)
            && is_null($ability->entity_id);
    }

    /**
     * Determines whether the given ability is for an action on a specific model.
     *
     * @param  \Illuminate\Database\Eloquent\model  $ability
     * @return bool
     */
    protected function isSpecificModelAbility(Model $ability)
    {
        return $ability->entity_type !== '*'
            && ! is_null($ability->entity_type)
            && ! is_null($ability->entity_id);
    }

    /**
     * Determines whether the given ability allows an action on all models.
     *
     * @param  \Illuminate\Database\Eloquent\model  $ability
     * @return bool
     */
    protected function isGlobalActionAbility(Model $ability)
    {
        return $ability->name !== '*'
            && $ability->entity_type === '*'
            && is_null($ability->entity_id);
    }

    /**
     * Get the title for the given wildcard ability.
     *
     * @param  \Illuminate\Database\Eloquent\model  $ability
     * @return string
     */
    protected function getWildcardAbilityTitle(Model $ability)
    {
        if ($ability->only_owned) {
            return 'Manage everything owned';
        }

        return 'All abilities';
    }

    /**
     * Get the title for the given blanket model ability.
     *
     * @param  \Illuminate\Database\Eloquent\model  $ability
     * @param  string  $name
     * @return string
     */
    protected function getBlanketModelAbilityTitle(Model $ability, $name = 'manage')
    {
        return $this->humanize($name.' '.$this->getPluralName($ability->entity_type));
    }

    /**
     * Get the title for the given model ability.
     *
     * @param  \Illuminate\Database\Eloquent\model  $ability
     * @return string
     */
    protected function getSpecificModelAbilityTitle(Model $ability)
    {
        $name = $ability->name === '*' ? 'manage' : $ability->name;

        return $this->humanize(
            $name.' '.$this->basename($ability->entity_type).' #'.$ability->entity_id
        );
    }

    /**
     * Get the human-readable plural form of the given class name.
     *
     * @param  string  $class
     * @return string
     */
    protected function getPluralName($class)
    {
        return $this->pluralize($this->basename($class));
    }

    /**
     * Get the class "basename" of the given class.
     *
     * @param  string  $class
     * @return string
     */
    protected function basename($class)
    {
        return basename(str_replace('\\', '/', $class));
    }

    /**
     * Pluralize the given value.
     *
     * @param  string  $value
     * @return string
     */
    protected function pluralize($value)
    {
        return Str::plural($value, 2);
    }
}
