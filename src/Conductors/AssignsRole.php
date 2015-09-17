<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Role;
use Illuminate\Database\Eloquent\Model;

class AssignsRole
{
    /**
     * The role to be assigned to a user.
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
     * Assign the role to the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Model|array|int  $user
     * @return bool
     */
    public function to($user)
    {
        $role = $this->role();

        if ($user instanceof Model) {
            $user = $user->getKey();
        }

        $users = is_array($user) ? $user : [$user];

        $role->users()->attach($users);

        return true;
    }

    /**
     * Get or create the role.
     *
     * @return \Silber\Bouncer\Database\Role
     */
    protected function role()
    {
        if ($this->role instanceof Role) {
            return $this->role;
        }

        return Role::firstOrCreate(['title' => $this->role]);
    }
}
