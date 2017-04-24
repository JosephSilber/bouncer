<?php

namespace Silber\Bouncer\Database;

use Closure;
use App\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;

class Models
{
    /**
     * The prefix for the tables.
     *
     * @var string
     */
    protected static $prefix = '';

    /**
     * Map for the bouncer's models.
     *
     * @var array
     */
    protected static $models = [];

    /**
     * Holds the map of ownership for models.
     *
     * @var array
     */
    protected static $ownership = [];

    /**
     * Map for the bouncer's tables.
     *
     * @var array
     */
    protected static $tables = [];

    /**
     * Set the model to be used for abilities.
     *
     * @param  string  $model
     * @return void
     */
    public static function setAbilitiesModel($model)
    {
        static::$models[Ability::class] = $model;
    }

    /**
     * Set the model to be used for roles.
     *
     * @param  string  $model
     * @return void
     */
    public static function setRolesModel($model)
    {
        static::$models[Role::class] = $model;
    }

    /**
     * Set the model to be used for users.
     *
     * @param  string  $model
     * @return void
     */
    public static function setUsersModel($model)
    {
        static::$models[User::class] = $model;
    }

    /**
     * Set custom table names.
     *
     * @param  array  $map
     * @return void
     */
    public static function setTables(array $map)
    {
        static::$tables = array_merge(static::$tables, $map);
    }

    /**
     * Set the prefix for the tables.
     *
     * @param  string  $prefix
     * @return void
     */
    public static function setPrefix($prefix)
    {
        static::$prefix = $prefix;
    }

    /**
     * Get a custom table name mapping for the given table.
     *
     * @param  string  $table
     * @return string
     */
    public static function table($table)
    {
        if (isset(static::$tables[$table])) {
            return static::$tables[$table];
        }

        return $table;
    }

    /**
     * Get the prefix for the tables.
     *
     * @return string
     */
    public static function prefix()
    {
        return static::$prefix;
    }

    /**
     * Get the classname mapping for the given model.
     *
     * @param  string  $model
     * @return string
     */
    public static function classname($model)
    {
        if (isset(static::$models[$model])) {
            return static::$models[$model];
        }

        return $model;
    }

    /**
     * Register an attribute/callback to determine if a model is owned by a given authority.
     *
     * @param  string|\Closure  $model
     * @param  string|\Closure|null  $attribute
     * @return void
     */
    public static function ownedVia($model, $attribute = null)
    {
        if (is_null($attribute)) {
            static::$ownership['*'] = $model;
        }

        static::$ownership[$model] = $attribute;
    }

    /**
     * Determines whether the given model is owned by the given authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    public static function isOwnedBy(Model $authority, Model $model)
    {
        $type = get_class($model);

        if (isset(static::$ownership[$type])) {
            $attribute = static::$ownership[$type];
        } elseif (isset(static::$ownership['*'])) {
            $attribute = static::$ownership['*'];
        } else {
            $attribute = strtolower(static::basename($authority)).'_id';
        }

        return static::isOwnedVia($attribute, $authority, $model);
    }

    /**
     * Determines ownership via the given attribute.
     *
     * @param  string|\Closure  $attribute
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected static function isOwnedVia($attribute, Model $authority, Model $model)
    {
        if ($attribute instanceof Closure) {
            return $attribute($model, $authority);
        }

        return $authority->getKey() == $model->{$attribute};
    }

    /**
     * Get an instance of the ability model.
     *
     * @param  array  $attributes
     * @return \Silber\Bouncer\Database\Ability
     */
    public static function ability(array $attributes = [])
    {
        return static::make(Ability::class, $attributes);
    }

    /**
     * Get an instance of the role model.
     *
     * @param  array  $attributes
     * @return \Silber\Bouncer\Database\Role
     */
    public static function role(array $attributes = [])
    {
        return static::make(Role::class, $attributes);
    }

    /**
     * Get an instance of the user model.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function user(array $attributes = [])
    {
        return static::make(User::class, $attributes);
    }

    /**
     * Get a new query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public static function newQueryBuilder()
    {
        return new Builder(
            $connection = static::user()->getConnection(),
            $connection->getQueryGrammar(),
            $connection->getPostProcessor()
        );
    }

    /**
     * Reset all settings to their original state.
     *
     * @return void
     */
    public static function reset()
    {
        static::$models = static::$tables = static::$ownership = [];
    }

    /**
     * Get an instance of the given model.
     *
     * @param  string  $model
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected static function make($model, array $attributes = [])
    {
        $model = static::classname($model);

        return new $model($attributes);
    }

    /**
     * Get the basename of the given class.
     *
     * @param  string|object  $class
     * @return string
     */
    protected static function basename($class)
    {
        if ( ! is_string($class)) {
            $class = get_class($class);
        }

        $segments = explode('\\', $class);

        return end($segments);
    }
}
