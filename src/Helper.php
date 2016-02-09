<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class Helper
{
    /**
     * Extract the model instance and model keys from the given parameters.
     *
     * @param  string|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection  $model
     * @param  array|null  $keys
     * @return array
     */
    public static function extractModelAndKeys($model, array $keys = null)
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

    /**
     * Map a list of authorities by their class name.
     *
     * @param  array  $authorities
     * @return array
     */
    public static function mapAuthorityByClass(array $authorities)
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
}
