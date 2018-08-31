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

    /**
     * Create a new instance from the raw data.
     *
     * @param  array  $data
     * @return static
     */
    public static function fromData(array $data)
    {
        $constraint = new static(
            $data['column'],
            $data['operator'],
            $data['value']
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
                'column' => $this->column,
                'operator' => $this->operator,
                'value' => $this->value,
                'logicalOperator' => $this->logicalOperator,
            ],
        ];
    }
}
