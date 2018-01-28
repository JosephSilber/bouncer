<?php

namespace Silber\Bouncer\Database\Concerns;

use Silber\Bouncer\Helpers;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Titles\RoleTitle;
use Silber\Bouncer\Database\Scope\BaseTenantScope;
use Silber\Bouncer\Database\Queries\Roles as RolesQuery;

use App\User;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

trait IsRole
{
    use HasAbilities, Authorizable {
        HasAbilities::getClipboardInstance insteadof Authorizable;
    }

    /**
     * Boot the is role trait.
     *
     * @return void
     */
    public static function bootIsRole()
    {
        BaseTenantScope::register(static::class);

        static::creating(function ($role) {
            Models::scope()->applyToModel($role);

            if (is_null($role->title)) {
                $role->title = RoleTitle::from($role)->toString();
            }
        });
    }

    /**
     * The users relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphedByMany
     */
    public function users()
    {
        $relation = $this->morphedByMany(
            Models::classname(User::class),
            'entity',
            Models::table('assigned_roles')
        );

        return Models::scope()->applyToRelation($relation);
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
        list($model, $keys) = Helpers::extractModelAndKeys($model, $keys);

        $query = $this->newBaseQueryBuilder()->from(Models::table('assigned_roles'));

        $query->insert($this->createAssignRecords($model, $keys));

        return $this;
    }

    /**
     * Find the given roles, creating the names that don't exist yet.
     *
     * @param  iterable  $roles
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findOrCreateRoles($roles)
    {
        $roles = Helpers::groupModelsAndIdentifiersByType($roles);

        $roles['integers'] = $this->find($roles['integers']);

        $roles['strings'] = $this->findOrCreateRolesByName($roles['strings']);

        return $this->newCollection(Arr::collapse($roles));
    }

    /**
     * Find roles by name, creating the ones that don't exist.
     *
     * @param  iterable  $names
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function findOrCreateRolesByName($names)
    {
        if (empty($names)) {
            return [];
        }

        $existing = static::whereIn('name', $names)->get()->keyBy('name');

        return (new Collection($names))
                ->diff($existing->pluck('name'))
                ->map(function ($name) {
                    return static::create(compact('name'));
                })
                ->merge($existing);
    }

    /**
     * Get the IDs of the given roles.
     *
     * @param  iterable  $roles
     * @return array
     */
    public function getRoleKeys($roles)
    {
        $roles = Helpers::groupModelsAndIdentifiersByType($roles);

        $roles['strings'] = $this->getKeysByName($roles['strings']);

        $roles['models'] = Arr::pluck($roles['models'], $this->getKeyName());

        return Arr::collapse($roles);
    }

    /**
     * Get the names of the given roles.
     *
     * @param  iterable  $roles
     * @return array
     */
    public function getRoleNames($roles)
    {
        $roles = Helpers::groupModelsAndIdentifiersByType($roles);

        $roles['integers'] = $this->getNamesByKey($roles['integers']);

        $roles['models'] = Arr::pluck($roles['models'], 'name');

        return Arr::collapse($roles);
    }

    /**
     * Get the keys of the roles with the given names.
     *
     * @param  iterable  $names
     * @return array
     */
    public function getKeysByName($names)
    {
        if (empty($names)) {
            return [];
        }

        return $this->whereIn('name', $names)
                    ->select($this->getKeyName())->get()
                    ->pluck($this->getKeyName())->all();
    }

    /**
     * Get the names of the roles with the given IDs.
     *
     * @param  iterable  $keys
     * @return array
     */
    public function getNamesByKey($keys)
    {
        if (empty($keys)) {
            return [];
        }

        return $this->whereIn($this->getKeyName(), $keys)
                    ->select('name')->get()
                    ->pluck('name')->all();
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
        list($model, $keys) = Helpers::extractModelAndKeys($model, $keys);

        $query = $this->newBaseQueryBuilder()
            ->from($table = Models::table('assigned_roles'))
            ->where('role_id', $this->getKey())
            ->where('entity_type', $model->getMorphClass())
            ->whereIn('entity_id', $keys);

        Models::scope()->applyToRelationQuery($query, $table);

        $query->delete();

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
            return Models::scope()->getAttachAttributes() + [
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
