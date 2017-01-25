<?php

namespace Silber\Bouncer\Database\Concerns;

use Silber\Bouncer\Helper;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Queries\Roles as RolesQuery;

use App\User;
use Illuminate\Database\Eloquent\Model;

trait IsRole
{
    use HasAbilities, Authorizable {
        HasAbilities::getClipboardInstance insteadof Authorizable;
    }

    /**
     * The users relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphedByMany
     */
    public function users()
    {
        return $this->morphedByMany(
            Models::classname(User::class),
            'entity',
            Models::table('assigned_roles')
        );
    }

    /**
     * Assign the role to the given model(s).
     *
     * @param  string|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection  $model
     * @param  array|null  $keys
     * @return $this
     */
    public function assignTo($model, array $keys = null)
    {
        list($model, $keys) = Helper::extractModelAndKeys($model, $keys);

        $query = $this->newBaseQueryBuilder()->from(Models::table('assigned_roles'));

        $query->insert($this->createAssignRecords($model, $keys));

        return $this;
    }

    /**
     * Retract the role from the given model(s).
     *
     * @param  string|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection  $model
     * @param  array|null  $keys
     * @return $this
     */
    public function retractFrom($model, array $keys = null)
    {
        list($model, $keys) = Helper::extractModelAndKeys($model, $keys);

        $this->newBaseQueryBuilder()
             ->from(Models::table('assigned_roles'))
             ->where('role_id', $this->getKey())
             ->where('entity_type', $model->getMorphClass())
             ->whereIn('entity_id', $keys)
             ->delete();

        return $this;
    }

    /**
     * Create the pivot table records for assigning the role to given models.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $keys
     * @return array
     */
    protected function createAssignRecords(Model $model, array $keys)
    {
        $type = $model->getMorphClass();

        return array_map(function ($key) use ($type) {
            return [
                'role_id'     => $this->getKey(),
                'entity_type' => $type,
                'entity_id'   => $key,
            ];
        }, $keys);
    }

    /**
     * Constrain the given query to roles that were assigned to the given authorities.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection  $model
     * @param  array  $keys
     * @return void
     */
    public function scopeWhereAssignedTo($query, $model, array $keys = null)
    {
        (new RolesQuery)->constrainWhereAssignedTo($query, $model, $keys);
    }
}
