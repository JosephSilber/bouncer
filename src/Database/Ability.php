<?php

namespace Silber\Bouncer\Database;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Ability extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'abilities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * Create a new ability for a specific model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $name
     * @return static
     */
    public static function createForModel(Model $model, $name)
    {
        return static::forceCreate([
            'name'        => $name,
            'entity_type' => $model->getMorphClass(),
            'entity_id'   => $model->exists ? $model->getKey() : null,
        ]);
    }

    /**
     * The roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Models::classname(Role::class), 'role_abilities');
    }

    /**
     * The users relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(Models::classname(User::class), 'user_abilities');
    }

    /**
     * Get the ability's "slug" attribute.
     *
     * @return string
     */
    public function getSlugAttribute()
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
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function scopeForModel($query, Model $model)
    {
        $query->where(function ($query) use ($model) {
            $query->where('entity_type', $model->getMorphClass());

            $query->where(function ($query) use ($model) {
                if ($model->exists) {
                    $query->orWhere('entity_id', $model->getKey());
                } else {
                    $query->whereNull('entity_id');
                }
            });
        });
    }
}
