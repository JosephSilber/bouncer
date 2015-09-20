<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Role;
use Illuminate\Database\Eloquent\Model;

class ChecksRole
{
    /**
     * The user against whom to check for roles.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $user;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model  $user
     */
    public function __construct(Model $user)
    {
        $this->user = $user;
    }

    /**
     * Check if the user has the given role.
     *
     * @param  string|array  $role
     * @param  string  $boolean
     * @return bool
     */
    public function a($role, $boolean = 'or')
    {
        if ($boolean == 'or') {
            return $this->query($role)->exists();
        }

        return $this->query($role)->count() == count((array) $role);
    }

    /**
     * Alias to the "a" method.
     *
     * @param  string|array  $role
     * @return bool
     */
    public function an($role)
    {
        return $this->a($role);
    }

    /**
     * Check if the user has all of the given roles.
     *
     * @param  string|array  $role
     * @return bool
     */
    public function all($role)
    {
        return $this->a($role, 'and');
    }

    /**
     * Create the base query to check for roles.
     *
     * @param  array|string  $role
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function query($role)
    {
        $relation = $this->user->roles();

        if (is_array($role)) {
            return $relation->whereIn('title', $role);
        }

        return $relation->where('title', $role);
    }
}
