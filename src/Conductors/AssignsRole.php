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
     * @var string
     */
    private $roleModelClass;

    /**
     * Constructor.
     *
     * @param \Silber\Bouncer\Database\Role|string $role
     * @param string $roleModelClass
     */
    public function __construct($role, $roleModelClass = 'Silber\Bouncer\Database\Role')
    {
        $this->role = $role;
        $this->roleModelClass = $roleModelClass;
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

        $ids = is_array($user) ? $user : [$user];

        $this->assignRole($role, $ids);

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

        return call_user_func($this->roleModelClass."::firstOrCreate", ['name' => $this->role]);
    }

    /**
     * Assign the role to the users with the given ids.
     *
     * @param  \Silber\Bouncer\Database\Role  $role
     * @param  array  $ids
     * @return void
     */
    protected function assignRole(Role $role, array $ids)
    {
        $existing = $role->users()->whereIn('id', $ids)->lists('id')->all();

        $ids = array_diff($ids, $existing);

        $role->users()->attach($ids);
    }
}
