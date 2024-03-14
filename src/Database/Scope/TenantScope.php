<?php

namespace Silber\Bouncer\Database\Scope;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope as EloquentScope;
use Silber\Bouncer\Database\Models;

class TenantScope implements EloquentScope
{
    /**
     * Register the correct global scope class.
     *
     * @param  string  $model
     * @return void
     */
    public static function register($model)
    {
        $model::addGlobalScope(new TenantScope);
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $query, Model $model)
    {
        Models::scope()->applyToModelQuery($query, $model->getTable());
    }
}
