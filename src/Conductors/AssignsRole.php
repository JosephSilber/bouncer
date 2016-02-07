<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class AssignsRole
{
    /**
     * The role to be assigned to an authority.
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
     * Assign the role to the given authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model|array|int  $authority
     * @return bool
     */
    public function to($authority)
    {
        $authorities = is_array($authority) ? $authority : [$authority];

        foreach ($this->mapAuthorityByClass($authorities) as $class => $keys) {
            $this->assignRole($this->role(), $class, $keys);
        }

        return true;
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

        return Models::role()->firstOrCreate(['name' => $this->role]);
    }

    /**
     * Assign the role to the authorities with the given keys.
     *
     * @param  \Silber\Bouncer\Database\Role  $role
     * @param  string  $class
     * @param  array  $keys
     * @return void
     */
    protected function assignRole(Role $role, $class, array $keys)
    {
        $existing = $this->getAuthoritiesWithRole($role, $class, $keys)->all();

        $keys = array_diff($keys, $existing);

        $role->assignTo($class, $keys);
    }

    /**
     * Get the keys of the authorities that already have the given role.
     *
     * @param  \Silber\Bouncer\Database\Role  $role
     * @param  string  $class
     * @param  array  $ids
     * @return \Illuminate\Support\Collection
     */
    protected function getAuthoritiesWithRole(Role $role, $class, array $ids)
    {
        $model = new $class;

        $column = $model->getTable().'.'.$model->getKeyName();

        return $model->whereIn($column, $ids)->whereIs($role->name)->lists($column);
    }
}
