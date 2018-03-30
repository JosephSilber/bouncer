<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Helpers;
use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Models;
use Illuminate\Database\Eloquent\Model;

class RemovesRoles
{
    /**
     * The roles to be removed.
     *
     * @var array
     */
    protected $roles;

    /**
     * Constructor.
     *
     * @param \Illuminate\Support\Collection|\Silber\Bouncer\Database\Role|string  $roles
     */
    public function __construct($roles)
    {
        $this->roles = Helpers::toArray($roles);
    }

    /**
     * Remove the role from the given authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model|array|int  $authority
     * @return void
     */
    public function from($authority)
    {
        if (! ($roleIds = $this->getRoleIds())) {
            return;
        }

        $authorities = is_array($authority) ? $authority : [$authority];

        foreach (Helpers::mapAuthorityByClass($authorities) as $class => $keys) {
            $this->retractRoles($roleIds, $class, $keys);
        }
    }

    /**
     * Get the IDs of anyexisting roles provided.
     *
     * @return array
     */
    protected function getRoleIds()
    {
        list($models, $names) = Helpers::partition($this->roles, function ($role) {
            return $role instanceof Model;
        });

        $ids = $models->map(function ($model) {
            return $model->getKey();
        });

        if ($names->count()) {
            $ids = $ids->merge($this->getRoleIdsFromNames($names->all()));
        }

        return $ids->all();
    }

    /**
     * Get the IDs of the roles with the given names.
     *
     * @param  string[]  $names
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getRoleIdsFromNames(array $names)
    {
        $key = Models::role()->getKeyName();

        return Models::role()
                     ->whereIn('name', $names)
                     ->get([$key])
                     ->pluck($key);
    }

    /**
     * Retract the given roles from the given authorities.
     *
     * @param  array  $roleIds
     * @param  string $authorityClass
     * @param  array $authorityIds
     * @return void
     */
    protected function retractRoles($roleIds, $authorityClass, $authorityIds)
    {
        $query = $this->newPivotTableQuery();

        $morphType = (new $authorityClass)->getMorphClass();

        foreach ($roleIds as $roleId) {
            foreach ($authorityIds as $authorityId) {
                $query->orWhere($this->getDetachQueryConstraint(
                    $roleId, $authorityId, $morphType
                ));
            }
        }

        $query->delete();
    }

    /**
     * Get a constraint for the detach query for the given parameters.
     *
     * @param  mixed  $roleId
     * @param  mixed  $authorityId
     * @param  string  $morphType
     * @return \Closure
     */
    protected function getDetachQueryConstraint($roleId, $authorityId, $morphType)
    {
        return function ($query) use ($roleId, $authorityId, $morphType) {
            $query->where(Models::scope()->getAttachAttributes() + [
                'role_id' => $roleId,
                'entity_id' => $authorityId,
                'entity_type' => $morphType,
            ]);
        };
    }

    /**
     * Get a query builder instance for the assigned roles pivot table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newPivotTableQuery()
    {
        return Models::newQueryBuilder()->from(Models::table('assigned_roles'));
    }
}
