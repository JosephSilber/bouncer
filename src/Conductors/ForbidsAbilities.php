<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Models;
use Illuminate\Database\Eloquent\Model;

class ForbidsAbilities
{
    use Concerns\AssociatesAbilities;

    /**
     * The authority to be forbidden from the abilities.
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
     * Forbid the abilities to the authority.
     *
     * @param  mixed  $abilities
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  array  $attributes
     * @return bool|\Silber\Bouncer\Conductors\Lazy\ConductsAbilities
     */
    public function to($abilities, $model = null, array $attributes = [])
    {
        if (call_user_func_array([$this, 'shouldConductLazy'], func_get_args())) {
            return $this->conductLazy($abilities);
        }

        $ids = $this->getAbilityIds($abilities, $model, $attributes);

        $this->forbidAbilities($ids, $this->getAuthority());

        return true;
    }

    /**
     * Associate the given abilitiy IDs as forbidden abilities.
     *
     * @param  array  $ids
     * @param  \Illuminate\Database\Eloquent\Model|null  $authority
     * @return void
     */
    protected function forbidAbilities(array $ids, Model $authority = null)
    {
        $ids = array_diff($ids, $this->getAssociatedAbilityIds($authority, $ids, true));

        if (is_null($authority)) {
            $this->forbidAbilitiesToEveryone($ids);
        } else {
            $this->forbidAbilitiesToAuthority($ids, $authority);
        }
    }

    /**
     * Forbid these abilities to the given authority.
     *
     * @param  array  $ids
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return void
     */
    protected function forbidAbilitiesToAuthority(array $ids, Model $authority)
    {
        $attributes = Models::scope()->getAttachAttributes(get_class($authority));

        $authority->abilities()->attach($ids, $attributes + ['forbidden' => true]);
    }

    /**
     * Forbid the given abilities for everyone.
     *
     * @param  array  $ids
     * @return void
     */
    protected function forbidAbilitiesToEveryone(array $ids)
    {
        $attributes = Models::scope()->getAttachAttributes() + ['forbidden' => true];

        $records = array_map(function ($id) use ($attributes) {
            return ['ability_id' => $id] + $attributes;
        }, $ids);

        Models::query('permissions')->insert($records);
    }
}
