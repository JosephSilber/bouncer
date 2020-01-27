<?php

namespace Silber\Bouncer\Database\Scope;

use Illuminate\Support\Collection;
use Silber\Bouncer\Database\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Scope as EloquentScope;

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
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $query, Model $model)
    {
        Models::scope()->applyToModelQuery($query, $model->getTable());
    }
}
