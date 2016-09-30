<?php

namespace Silber\Bouncer\Conductors;

use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Conductors\Traits\ConductsAbilities;
use Silber\Bouncer\Conductors\Traits\AssociatesAbilities;

class ForbidsAbility
{
    use AssociatesAbilities, ConductsAbilities;

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
     * @param  bool  $onlyOwned
     * @return bool
     */
    public function to($abilities, $model = null, $onlyOwned = false)
    {
        $ids = $this->getAbilityIds($abilities, $model, $onlyOwned);

        $this->forbidAbilities($ids, $this->getAuthority());

        return true;
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
