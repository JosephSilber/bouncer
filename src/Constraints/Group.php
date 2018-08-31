<?php

namespace Silber\Bouncer\Constraints;

use Silber\Bouncer\Helpers;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class Group implements Constrainer
{
    /**
     * The list of constraints.
     *
     * @var \Illuminate\Support\Collection<\Silber\Bouncer\Constraints\Constrainer>
     */
    protected $constraints;

    /**
     * The logical operator to use when checked after a previous constrainer.
     *
     * @var string
     */
    protected $logicalOperator = 'and';

    /**
     * Constructor.
     *
     * @param iterable<\Silber\Bouncer\Constraints\Constrainer>  $constraints
     */
    public function __construct($constraints = [])
    {
        $this->constraints = new Collection($constraints);
    }

    /**
     * Create a new "and" group.
     *
     * @return static
     */
    public static function withAnd()
    {
        return new static;
    }

    /**
     * Create a new "and" group.
     *
     * @return static
     */
    public static function withOr()
    {
        return (new static)->logicalOperator('or');
    }

    /**
     * Create a new instance from the raw data.
     *
     * @param  array  $data
     * @return static
     */
    public static function fromData(array $data)
    {
        $group = new static(array_map(function ($data) {
            return $data['class']::fromData($data['params']);
        }, $data['constraints']));

        return $group->logicalOperator($data['logicalOperator']);
    }

    /**
     * Add the given constraint to the list of constraints.
     *
     * @param \Silber\Bouncer\Constraints\Constrainer  $constraint
     */
    public function add(Constrainer $constraint)
    {
        $this->constraints->push($constraint);

        return $this;
    }

    /**
     * Determine whether the given entity/authority passes this constraint.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @param  \Illuminate\Database\Eloquent\Model|null  $authority
     * @return bool
     */
    public function check(Model $entity, Model $authority = null)
    {
        if ($this->constraints->isEmpty()) {
            return true;
        }

        return $this->constraints->reduce(function ($result, $constraint) use ($entity, $authority) {
            $passes = $constraint->check($entity, $authority);

            if (is_null($result)) {
                return $passes;
            }

            return $constraint->isOr() ? ($result || $passes) : ($result && $passes);
        });
    }

    /**
     * Set the logical operator to use when checked after a previous constrainer.
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

        if ($this->constraints->count() != $constrainer->constraints->count()) {
            return false;
        }

        foreach ($this->constraints as $index => $constraint) {
            if (! $constrainer->constraints[$index]->equals($constraint)) {
                return false;
            }
        }

        return true;
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
                'logicalOperator' => $this->logicalOperator,
                'constraints' => $this->constraints->map(function ($constraint) {
                    return $constraint->data();
                })->all(),
            ],
        ];
    }
}
