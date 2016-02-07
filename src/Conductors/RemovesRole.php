<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

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

        foreach ($this->mapAuthorityByClass($authorities) as $class => $keys) {
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

    /**
     * Map a list of authorities by their class name.
     *
     * @param  array  $authorities
     * @return array
     */
    protected function mapAuthorityByClass(array $authorities)
    {
        $map = [];

        foreach ($authorities as $authority) {
            if ($authority instanceof Model) {
                $map[get_class($authority)][] = $authority->getKey();
            } else {
                $map[Models::classname(User::class)][] = $authority;
            }
        }

        return $map;
    }
}
