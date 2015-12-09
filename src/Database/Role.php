<?php

namespace Silber\Bouncer\Database;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Constraints\Abilities as AbilitiesConstraint;

class Role extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * The abilities relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function abilities()
    {
        return $this->belongsToMany(Models::classname(Ability::class), 'role_abilities');
    }

    /**
     * The users relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(Models::classname(User::class), 'user_roles');
    }

    /**
     * Constrain the given query by the provided ability.
     *
     * @param  \Illuminate\Database\Eloquent\Query  $query
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return void
     */
    public function scopeWhereCan($query, $ability, $model = null)
    {
        (new AbilitiesConstraint)->constrainRoles($query, $ability, $model);
    }
}
