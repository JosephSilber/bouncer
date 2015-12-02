<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Models;

use Illuminate\Database\Eloquent\Model;

class RemovesRole
{
    /**
     * The role to be removed from a user.
     *
     * @var \Silber\Bouncer\Database\Role|string
     */
    protected $role;

    /**
     * Constructor.
     *
     * @param \Silber\Bouncer\Database\Role|string  $role
     */
    public function __construct($role)
    {
        $this->role = $role;
    }

    /**
     * Remove the role from the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Model|array|int  $user
     * @return bool
     */
    public function from($user)
    {
        if ( ! $role = $this->role()) {
            return false;
        }

        if ($user instanceof Model) {
            $user = $user->getKey();
        }

        $users = is_array($user) ? $user : [$user];

        $role->users()->detach($user);

        return true;
    }

    /**
     * Get the role.
     *
     * @return \Silber\Bouncer\Database\Role|null
     */
    protected function role()
    {
        if ($this->role instanceof Role) {
            return $this->role;
        }

        return Models::role()->where('name', $this->role)->first();
    }
}
