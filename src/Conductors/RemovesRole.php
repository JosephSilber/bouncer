<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Role;
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
     * @var string
     */
    private $roleModelClass;

    /**
     * @var string
     */
    private $abilityModelClass;

    /**
     * Constructor.
     *
     * @param \Silber\Bouncer\Database\Role|string $role
     * @param string $roleModelClass
     * @param string $abilityModelClass
     */
    public function __construct($role, $roleModelClass = 'Silber\Bouncer\Database\Role', $abilityModelClass = 'Silber\Bouncer\Database\Ability')
    {
        $this->role = $role;
        $this->roleModelClass = $roleModelClass;
        $this->abilityModelClass = $abilityModelClass;
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

        return call_user_func($this->roleModelClass."::where", 'name', $this->role)->first();
    }
}
