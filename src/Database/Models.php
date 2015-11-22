<?php

namespace Silber\Bouncer\Database;

use App\User;
use InvalidArgumentException;

class Models
{
    /**
     * Map for the bouncer's models.
     *
     * @var array
     */
    protected static $models = [
        User::class => User::class,
        Role::class => Role::class,
        Ability::class => Ability::class,
    ];

    /**
     * Set the model to be used for abilities.
     *
     * @param string  $model
     */
    public static function setAbilitiesModel($model)
    {
        static::$models[Ability::class] = $model;
    }

    /**
     * Set the model to be used for roles.
     *
     * @param string  $model
     */
    public static function setRolesModel($model)
    {
        static::$models[Role::class] = $model;
    }

    /**
     * Set the model to be used for users.
     *
     * @param string  $model
     */
    public static function setUsersModel($model)
    {
        static::$models[User::class] = $model;
    }

    /**
     * Get the final class name for the given parent model.
     *
     * @param  string  $model
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public static function classname($model)
    {
        if (isset(static::$models[$model])) {
            return static::$models[$model];
        }

        throw new InvalidArgumentException("The '{$model}' classname has no mapping.");
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
     * Get an instance of the role model.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function user(array $attributes = [])
    {
        return static::make(User::class, $attributes);
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
}
