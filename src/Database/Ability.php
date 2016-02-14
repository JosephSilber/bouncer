<?php

namespace Silber\Bouncer\Database;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Constraints\AbilitiesForModel;

class Ability extends Model
{
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
        $this->table = Models::table('abilities');

        parent::__construct($attributes);
    }

    /**
     * Create a new ability for a specific model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  string  $name
     * @return static
     */
    public static function createForModel($model, $name)
    {
        if ($model == '*') {
            return static::createForModelWildcard($name);
        }

        return static::forceCreate([
            'name'        => $name,
            'entity_type' => $model->getMorphClass(),
            'entity_id'   => $model->exists ? $model->getKey() : null,
        ]);
    }

    /**
     * Create a new ability for all model.
     *
     * @param  string  $name
     * @return static
     */
    public static function createForModelWildcard($name)
    {
        return static::forceCreate([
            'name'        => $name,
            'entity_type' => '*',
        ]);
    }

    /**
     * The roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function roles()
    {
        return $this->morphedByMany(
            Models::classname(Role::class),
            'entity',
            Models::table('permissions')
        );
    }

    /**
     * The users relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users()
    {
        return $this->morphedByMany(
            Models::classname(User::class),
            'entity',
            Models::table('permissions')
        );
    }

    /**
     * Get the identifier for this ability.
     *
     * @return string
     */
    final public function getIdentifierAttribute()
    {
        $slug = $this->attributes['name'];

        if ($this->attributes['entity_type']) {
            $slug .= '-'.$this->attributes['entity_type'];
        }

        if ($this->attributes['entity_id']) {
            $slug .= '-'.$this->attributes['entity_id'];
        }

        return strtolower($slug);
    }

    /**
     * Get the ability's "slug" attribute.
     *
     * @return string
     */
    public function getSlugAttribute()
    {
        return $this->getIdentifierAttribute();
    }

    /**
     * Constrain a query to having the given name.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @return string|array  $name
     * @return bool  $strict
     * @return void
     */
    public function scopeByName($query, $name, $strict = false)
    {
        $names = (array) $name;

        if ( ! $strict) {
            $names[] = '*';
        }

        $query->whereIn("{$this->table}.name", $names);
    }

    /**
     * Constrain a query to simple abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @return void
     */
    public function scopeSimpleAbility($query)
    {
        $query->where(function ($query) {
            $query->whereNull('entity_id')->whereNull('entity_type');
        });
    }

    /**
     * Constrain a query to an ability for a specific model.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  bool  $strict
     * @return void
     */
    public function scopeForModel($query, $model, $strict = false)
    {
        (new AbilitiesForModel)->constrain($query, $model, $strict);
    }
}
