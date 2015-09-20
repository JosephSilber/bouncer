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
        $roles = (array) $role;

        $query = $this->user->roles()->whereIn('title', $roles);

        if ($boolean == 'or') {
            return $query->exists();
        }

        return $query->count() == count($roles);
    }

    /**
     * Alias to the "a" method.
     *
     * @param  string|array  $role
     * @param  string  $boolean
     * @return bool
     */
    public function an($role, $boolean = 'or')
    {
        return $this->a($role, $boolean);
    }
}
