<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Helper;
use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Models;

class RemovesRole
{
    /**
     * The role to be removed from an authority.
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
     * Remove the role from the given authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model|array|int  $authority
     * @return bool
     */
    public function from($authority)
    {
        if (is_null($role = $this->role())) {
            return false;
        }

        $authorities = is_array($authority) ? $authority : [$authority];

        foreach (Helper::mapAuthorityByClass($authorities) as $class => $keys) {
            $role->retractFrom($class, $keys);
        }

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
