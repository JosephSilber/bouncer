<?php

namespace Silber\Bouncer\Constraints;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;

abstract class Constraint implements Constrainer
{
    /**
     * The operator to use for the comparison.
     *
     * @var string
     */
    protected $operator;

    /**
     * Determine whether the given entity/authority passes this constraint.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @param  \Illuminate\Database\Eloquent\Model|null  $authority
     * @return bool
     */
    abstract public function check(Model $entity, Model $authority = null);

    /**
     * Create a new constraint for where a column matches the given value.
     *
     * @param  string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return \Silber\Bouncer\Constraints\ValueConstraint
     */
    public static function where($column, $operator, $value = null)
    {
        list($operator, $value) = static::prepareOperatorAndValue(
            $operator, $value, func_num_args() === 2
        );

        return new ValueConstraint($column, $operator, $value);
    }

    /**
     * Create a new constraint for where a column matches the given column on the authority.
     *
     * @param  string  $a
     * @param  mixed  $operator
     * @param  mixed  $b
     * @return \Silber\Bouncer\Constraints\ColumnConstraint
     */
    public static function whereColumn($a, $operator, $b = null)
    {
        list($operator, $b) = static::prepareOperatorAndValue(
            $operator, $b, func_num_args() === 2
        );

        return new ColumnConstraint($a, $operator, $b);
    }

    /**
     * Prepare the value and operator.
     *
     * @param  string  $operator
     * @param  string  $value
     * @param  bool  $usesDefault
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected static function prepareOperatorAndValue($operator, $value, $usesDefault)
    {
        if ($usesDefault) {
            return ['=', $operator];
        }

        if (!in_array($operator, ['=', '==', '!=', '<', '>', '<=', '>='])) {
            throw new InvalidArgumentException("{$operator} is not a valid operator");
        }

        return [$operator, $value];
    }

    /**
     * Compare the two values by the constraint's operator.
     *
     * @param  mixed  $a
     * @param  mixed  $b
     * @return bool
     */
    protected function compare($a, $b)
    {
        switch ($this->operator) {
            case '=':
            case '==': return $a == $b;
            case '!=': return $a != $b;
            case '<':  return $a < $b;
            case '>':  return $a > $b;
            case '<=': return $a <= $b;
            case '>=': return $a >= $b;
        }
    }
}
