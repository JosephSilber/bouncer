<?php

namespace Silber\Bouncer\Constraints;

use Illuminate\Database\Eloquent\Model;

class ColumnConstraint extends Constraint
{

    /**
     * The column on the entity against which to compare.
     *
     * @var string
     */
    protected $a;

    /**
     * The column on the authority against which to compare.
     *
     * @var mixed
     */
    protected $b;

    /**
     * Constructor.
     *
     * @param string  $a
     * @param string  $operator
     * @param mixed  $b
     */
    public function __construct($a, $operator, $b)
    {
        $this->a = $a;
        $this->operator = $operator;
        $this->b = $b;
    }

    /**
     * Determine whether the given entity/authority passes the constraint.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @param  \Illuminate\Database\Eloquent\Model|null  $authority
     * @return bool
     */
    public function check(Model $entity, Model $authority = null)
    {
        if (is_null($authority)) {
            return false;
        }

        return $this->compare($entity->{$this->a}, $authority->{$this->b});
    }
}
