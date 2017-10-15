<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Helpers;
use Illuminate\Support\Collection;
use Silber\Bouncer\Database\Models;
use Illuminate\Database\Eloquent\Model;

class AssignsRoles
{
    /**
     * The roles to be assigned.
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
     * Assign the roles to the given authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model|array|int  $authority
     * @return bool
     */
    public function to($authority)
    {
        $authorities = is_array($authority) ? $authority : [$authority];

        $roles = Models::role()->findOrCreateRoles($this->roles);

        foreach (Helpers::mapAuthorityByClass($authorities) as $class => $ids) {
            $this->assignRoles($roles, $class, new Collection($ids));
        }

        return true;
    }

    /**
     * Assign the given roles to the given authorities.
     *
     * @param  \Illuminate\Suuport\Collection  $roles
     * @param  string $authorityClass
     * @param  \Illuminate\Suuport\Collection  $authorityIds
     * @return void
     */
    protected function assignRoles(Collection $roles, $authorityClass, Collection $authorityIds)
    {
        $roleIds = $roles->map(function ($model) {
            return $model->getKey();
        });

        $morphType = (new $authorityClass)->getMorphClass();

        $records = $this->buildAttachRecords($roleIds, $morphType, $authorityIds);

        $existing = $this->getExistingAttachRecords($roleIds, $morphType, $authorityIds);

        $this->createMissingAssignRecords($records, $existing);
    }

    /**
     * Get the pivot table records for the roles already assigned.
     *
     * @param  \Illuminate\Suuport\Collection  $roleIds
     * @param  string $morphType
     * @param  \Illuminate\Suuport\Collection  $authorityIds
     * @return \Illuminate\Support\Collection
     */
    protected function getExistingAttachRecords($roleIds, $morphType, $authorityIds)
    {
        $query = $this->newPivotTableQuery()
            ->whereIn('role_id', $roleIds->all())
            ->whereIn('entity_id', $authorityIds->all())
            ->where('entity_type', $morphType);

        Models::scope()->applyToRelationQuery($query, $query->from);

        return new Collection($query->get());
    }

    /**
     * Build the raw attach records for the assigned roles pivot table.
     *
     * @param  \Illuminate\Suuport\Collection  $roleIds
     * @param  string $morphType
     * @param  \Illuminate\Suuport\Collection  $authorityIds
     * @return \Illuminate\Support\Collection
     */
    protected function buildAttachRecords($roleIds, $morphType, $authorityIds)
    {
        return $roleIds->map(function ($roleId) use ($morphType, $authorityIds) {
            return $authorityIds->map(function ($authorityId) use ($roleId, $morphType) {
                return Models::scope()->getAttachAttributes() + [
                    'role_id' => $roleId,
                    'entity_id' => $authorityId,
                    'entity_type' => $morphType,
                ];
            });
        })->collapse();
    }

    /**
     * Save the non-existing attach records in the DB.
     *
     * @param  \Illuminate\Support\Collection  $records
     * @param  \Illuminate\Support\Collection  $existing
     * @return void
     */
    protected function createMissingAssignRecords(Collection $records, Collection $existing)
    {
        $existing = $existing->keyBy(function ($record) {
            return $this->getAttachRecordHash((array) $record);
        });

        $records = $records->reject(function ($record) use ($existing) {
            return $existing->has($this->getAttachRecordHash($record));
        });

        $this->newPivotTableQuery()->insert($records->all());
    }

    /**
     * Get a string identifying the given attach record.
     *
     * @param  array  $record
     * @return string
     */
    protected function getAttachRecordHash(array $record)
    {
        return $record['role_id'].$record['entity_id'].$record['entity_type'];
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
