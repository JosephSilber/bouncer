<?php

namespace Silber\Bouncer\Constraints;

use Silber\Bouncer\Helpers;
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
     * The logical operator to use when checked after a previous constaint.
     *
     * @var string
     */
    protected $logicalOperator = 'and';

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
     * Create a new constraint for where a column matches the given value,
     * with the logical operator set to "or".
     *
     * @param  string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return \Silber\Bouncer\Constraints\ValueConstraint
     */
    public static function orWhere($column, $operator, $value = null)
    {
        return static::where(...func_get_args())->logicalOperator('or');
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
     * Create a new constraint for where a column matches the given column on the authority,
     * with the logical operator set to "or".
     *
     * @param  string  $a
     * @param  mixed  $operator
     * @param  mixed  $b
     * @return \Silber\Bouncer\Constraints\ColumnConstraint
     */
    public static function orWhereColumn($a, $operator, $b = null)
    {
        return static::whereColumn(...func_get_args())->logicalOperator('or');
    }

    /**
     * Set the logical operator to use when checked after a previous constraint.
     *
     * @param  string|null  $operator
     * @return $this|string
     */
    public function logicalOperator($operator = null)
    {
        if (is_null($operator)) {
            return $this->logicalOperator;
        }

        Helpers::ensureValidLogicalOperator($operator);

        $this->logicalOperator = $operator;

        return $this;
    }

    /**
     * Checks whether the logical operator is an "and" operator.
     *
     * @param string  $operator
     */
    public function isAnd()
    {
        return $this->logicalOperator == 'and';
    }

    /**
     * Checks whether the logical operator is an "and" operator.
     *
     * @param string  $operator
     */
    public function isOr()
    {
        return $this->logicalOperator == 'or';
    }

    /**
     * Determine whether the given constrainer is equal to this object.
     *
     * @param  \Silber\Bouncer\Constraints\Constrainer  $constrainer
     * @return bool
     */
    public function equals(Constrainer $constrainer)
    {
        if (! $constrainer instanceof static) {
            return false;
        }

        return $this->data()['params'] == $constrainer->data()['params'];
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

        if (! in_array($operator, ['=', '==', '!=', '<', '>', '<=', '>='])) {
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
