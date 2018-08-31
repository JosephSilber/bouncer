<?php

namespace Silber\Bouncer\Constraints;

use Closure;
use InvalidArgumentException;
use Illuminate\Support\Collection;

class Builder
{
    /**
     * The list of constraints.
     *
     * @var \Illuminate\Support\Collection<\Silber\Bouncer\Constraints\Constraint|static>
     */
    protected $constraints;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        $this->constraints = new Collection;
    }

    /**
     * Create a new builder instance.
     *
     * @return static
     */
    public static function make()
    {
        return new static;
    }

    /**
     * Add a "where" constraint.
     *
     * @param  string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function where($column, $operator = null, $value = null)
    {
        if ($column instanceof Closure) {
            return $this->whereNested('and', $column);
        }

        $constraint = call_user_func_array(
            [Constraint::class, 'where'],
            func_get_args()
        );

        return $this->addConstraint('and', $constraint);
    }

    /**
     * Add an "or where" constraint.
     *
     * @param  string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        if ($column instanceof Closure) {
            return $this->whereNested('or', $column);
        }

        $constraint = call_user_func_array(
            [Constraint::class, 'where'],
            func_get_args()
        );

        return $this->addConstraint('or', $constraint);
    }

    /**
     * Add a "where column" constraint.
     *
     * @param  string  $a
     * @param  mixed  $operator
     * @param  mixed  $b
     * @return $this
     */
    public function whereColumn($a, $operator, $b = null)
    {
        $constraint = call_user_func_array(
            [Constraint::class, 'whereColumn'],
            func_get_args()
        );

        return $this->addConstraint('and', $constraint);
    }

    /**
     * Add an "or where column" constraint.
     *
     * @param  string  $a
     * @param  mixed  $operator
     * @param  mixed  $b
     * @return $this
     */
    public function orWhereColumn($a, $operator, $b = null)
    {
        $constraint = call_user_func_array(
            [Constraint::class, 'whereColumn'],
            func_get_args()
        );

        return $this->addConstraint('or', $constraint);
    }

    /**
     * Build the constraints.
     *
     * @return \Silber\Bouncer\Constraints\Constrainer|null
     */
    public function build()
    {
        if ($this->constraints->isEmpty()) {
            return null;
        }

        if ($this->constraints->count() == 1) {
            return $this->constraints->first()['source'];
        }

        return $this->buildGroup();
    }

    /**
     * Build the group of constraints.
     *
     * @return \Silber\Bouncer\Constraints\Group
     */
    protected function buildGroup()
    {
        $group = new AndGroup;

        foreach ($this->constraints as $constraint) {
            $constrainer = $this->buildConstrainer($constraint['source']);

            if ($group->isOfType($constraint['logicalOperator'])) {
                $group->add($constrainer);
            } else {
                $group = Group::ofType($constraint['logicalOperator'])
                    ->add($group->isSingle() ? $group->constraints()->first() : $group)
                    ->add($constrainer);
            }
        }

        return $group;
    }

    /**
     * Extract the constrainer from the given object.
     *
     * @param \Silber\Bouncer\Constraints\Constraint|self  $object
     */
    protected function buildConstrainer($object)
    {
        return $object instanceof self ? $object->build() : $object;
    }

    /**
     * Add a nested "where" clause.
     *
     * @param  string  $logicalOperator  'and'|'or'
     * @param  \Closure  $callback
     * @return $this
     */
    protected function whereNested($logicalOperator, Closure $callback)
    {
        $callback($builder = new static);

        return $this->addConstraint($logicalOperator, $builder);
    }

    /**
     * Add a constraint to the list of constraints.
     *
     * @param  string  $logicalOperator  'and'|'or'
     * @param  \Silber\Bouncer\Constraints\Constrainer|self  $source
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    protected function addConstraint($logicalOperator, $source)
    {
        if (! in_array($logicalOperator, ['and', 'or'])) {
            throw new InvalidArgumentException(
                "{$logicalOperator} is an invalid logical operator"
            );
        }

        // If we don't have any constraints yet, it doesn't make sense
        // to start with an "or" operator, because there is nothing
        // coming before it. We change it to the "and" operator.
        if ($this->constraints->isEmpty()) {
            $logicalOperator = 'and';
        }

        $this->constraints[] = compact('logicalOperator', 'source');

        return $this;
    }
}
