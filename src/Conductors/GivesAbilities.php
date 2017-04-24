<?php

namespace Silber\Bouncer\Conductors;

use Illuminate\Database\Eloquent\Model;

class GivesAbilities
{
    use Concerns\AssociatesAbilities;

    /**
     * The authority to be given abilities.
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
     * Give the abilities to the authority.
     *
     * @param  \Illuminate\Database\Eloquent\model|array|int  $abilities
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  array  $attributes
     * @return void
     */
    public function to($abilities, $model = null, array $attributes = [])
    {
        $ids = $this->getAbilityIds($abilities, $model, $attributes);

        $this->giveAbilities($ids, $this->getAuthority());
    }

    /**
     * Associate the given ability IDs as allowed abilities.
     *
     * @param  array  $ids
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return void
     */
    protected function giveAbilities(array $ids, Model $authority)
    {
        $ids = array_diff($ids, $this->getAssociatedAbilityIds($authority, $ids, false));

        $authority->abilities()->attach($ids);
    }
}
