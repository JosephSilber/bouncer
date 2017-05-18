<?php

namespace Silber\Bouncer\Database\Queries;

use Silber\Bouncer\Database\Models;
use Illuminate\Database\Eloquent\Model;

class AbilitiesForModel
{
    /**
     * The name of the abilities table.
     *
     * @var string
     */
    protected $table;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        $this->table = Models::table('abilities');
    }

    /**
     * Constrain a query to an ability for a specific model or wildcard.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  bool  $strict
     * @return void
     */
    public function constrain($query, $model, $strict = false)
    {
        if ($model === '*') {
            return $this->constrainByWildcard($query, $strict);
        }

        $model = is_string($model) ? new $model : $model;

        $this->constrainByModel($query, $model, $strict);
    }

    /**
     * Constrain a query to a model wiildcard.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  bool  $strict
     * @return void
     */
    protected function constrainByWildcard($query, $strict = false)
    {
        if ( ! $strict) {
            return;
        }

        $query->where("{$this->table}.entity_type", '*');
    }

    /**
     * Constrain a query to an ability for a specific model.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  bool  $strict
     * @return void
     */
    protected function constrainByModel($query, Model $model, $strict = false)
    {
        $query->where(function ($query) use ($model, $strict) {
            $query->where($this->table.'.entity_type', $model->getMorphClass());

            $query->where($this->abilitySubqueryConstraint($model, $strict));
        });
    }

    /**
     * Get the constraint for the ability subquery.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  bool  $strict
     * @return \Closure
     */
    protected function abilitySubqueryConstraint(Model $model, $strict)
    {
        return function ($query) use ($model, $strict) {
            // If the model does not exist, we want to search for blanket abilities
            // that cover all instances of this model. If it does exist, we only
            // want to find blanket abilities if we're not using strict mode.
            if ( ! $model->exists || ! $strict) {
                $query->whereNull($this->table.'.entity_id');
            }

            if ($model->exists) {
                $query->orWhere($this->table.'.entity_id', $model->getKey());
            }
        };
    }
}
