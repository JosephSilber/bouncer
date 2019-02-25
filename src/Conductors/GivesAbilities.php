<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Models;
use Illuminate\Database\Eloquent\Model;

class GivesAbilities
{
    use Concerns\AssociatesAbilities;

    /**
     * The authority to be given abilities.
     *
     * @var \Illuminate\Database\Eloquent\Model|string|null
     */
    protected $authority;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model|string|null  $authority
     */
    public function __construct($authority = null)
    {
        $this->authority = $authority;
    }

    /**
     * Give the abilities to the authority.
     *
     * @param  \Illuminate\Database\Eloquent\model|array|int  $abilities
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  array  $attributes
     * @return \Silber\Bouncer\Conductors\Lazy\ConductsAbilities|null
     */
    public function to($abilities, $model = null, array $attributes = [])
    {
        if (call_user_func_array([$this, 'shouldConductLazy'], func_get_args())) {
            return $this->conductLazy($abilities);
        }

        $ids = $this->getAbilityIds($abilities, $model, $attributes);

        $this->giveAbilities($ids, $this->getAuthority());
    }

    /**
     * Associate the given ability IDs as allowed abilities.
     *
     * @param  array  $ids
     * @param  \Illuminate\Database\Eloquent\Model|null  $authority
     * @return void
     */
    protected function giveAbilities(array $ids, Model $authority = null)
    {
        $ids = array_diff($ids, $this->getAssociatedAbilityIds($authority, $ids, false));

        if (is_null($authority)) {
            $this->giveAbilitiesToEveryone($ids);
        } else {
            $this->giveAbilitiesToAuthority($ids, $authority);
        }
    }

    /**
     * Grant permission to these abilities to the given authority.
     *
     * @param  array  $ids
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return void
     */
    protected function giveAbilitiesToAuthority(array $ids, Model $authority)
    {
        $attributes = Models::scope()->getAttachAttributes(get_class($authority));

        $authority->abilities()->attach($ids, $attributes);
    }

    /**
     * Grant everyone permission to the given abilities.
     *
     * @param  array  $ids
     * @return void
     */
    protected function giveAbilitiesToEveryone(array $ids)
    {
        $attributes = Models::scope()->getAttachAttributes();

        $records = array_map(function ($id) use ($attributes) {
            return ['ability_id' => $id] + $attributes;
        }, $ids);

        Models::query('permissions')->insert($records);
    }
}
