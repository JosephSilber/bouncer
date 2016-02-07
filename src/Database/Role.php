<?php

namespace Silber\Bouncer\Database;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Silber\Bouncer\Database\Constraints\Abilities as AbilitiesConstraint;

class Role extends Model
{
    use HasAbilities;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $attributes = [])
    {
        $this->table = Models::table('roles');

        parent::__construct($attributes);
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
        list($model, $keys) = $this->extractModelAndKeys($model, $keys);

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
        list($model, $keys) = $this->extractModelAndKeys($model, $keys);

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
     * Extract the model instance and model keys from the given parameters.
     *
     * @param  string|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection  $model
     * @param  array  $keys
     * @return array
     */
    protected function extractModelAndKeys($model, array $keys = null)
    {
        if (is_null($keys)) {
            if ($model instanceof Model) {
                return [$model, [$model->getKey()]];
            }

            if ($model instanceof Collection) {
                return [$model->first(), $model->modelKeys()];
            }
        } else {
            if (is_string($model)) {
                $model = new $model;
            }

            return [$model, $keys];
        }
    }
}
