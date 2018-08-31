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

    /**
     * Create a new instance from the raw data.
     *
     * @param  array  $data
     * @return static
     */
    public static function fromData(array $data)
    {
        $constraint = new static(
            $data['a'],
            $data['operator'],
            $data['b']
        );

        return $constraint->logicalOperator($data['logicalOperator']);
    }

    /**
     * Get the JSON-able data of this object.
     *
     * @return array
     */
    public function data()
    {
        return [
            'class' => static::class,
            'params' => [
                'a' => $this->a,
                'operator' => $this->operator,
                'b' => $this->b,
                'logicalOperator' => $this->logicalOperator,
            ],
        ];
    }
}
