<?php

namespace Silber\Bouncer\Conductors;

use Illuminate\Database\Eloquent\Model;

class ForbidsAbilities
{
    use Concerns\AssociatesAbilities;

    /**
     * The authority to be forbidden from the abilities.
     *
     * @var \Illuminate\Database\Eloquent\Model|string
     */
    protected $authority;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model|string  $authority
     */
    public function __construct($authority)
    {
        $this->authority = $authority;
    }

    /**
     * Forbid the abilities to the authority.
     *
     * @param  mixed  $abilities
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  array  $attributes
     * @return bool
     */
    public function to($abilities, $model = null, array $attributes = [])
    {
        $ids = $this->getAbilityIds($abilities, $model, $attributes);

        $this->forbidAbilities($ids, $this->getAuthority());

        return true;
    }

    /**
     * Forbid the given ability on all models.
     *
     * @param  array|string  $abilities
     * @param  array  $attributes
     * @return mixed
     */
    public function toEver($abilities, array $attributes = [])
    {
        return $this->toAlways($abilities, $attributes);
    }

    /**
     * Associate the given abilitiy IDs as forbidden abilities.
     *
     * @param  array  $ids
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return void
     */
    protected function forbidAbilities(array $ids, Model $authority)
    {
        $ids = array_diff($ids, $this->getAssociatedAbilityIds($authority, $ids, true));

        $authority->abilities()->attach($ids, ['forbidden' => true]);
    }
}
