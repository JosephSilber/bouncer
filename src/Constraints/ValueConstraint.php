<?php

namespace Silber\Bouncer\Constraints;

use Illuminate\Database\Eloquent\Model;

class ValueConstraint extends Constraint
{
    /**
     * The column on the entity against which to compare.
     *
     * @var string
     */
    protected $column;

    /**
     * The value to compare to.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Constructor.
     *
     * @param string  $column
     * @param string  $operator
     * @param mixed  $value
     */
    public function __construct($column, $operator, $value)
    {
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * Determine whether the given entity/authority passed this constraint.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @param  \Illuminate\Database\Eloquent\Model|null  $authority
     * @return bool
     */
    public function check(Model $entity, Model $authority = null)
    {
        return $this->compare($entity->{$this->column}, $this->value);
    }
}
