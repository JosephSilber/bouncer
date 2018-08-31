<?php

namespace Silber\Bouncer\Constraints;

use InvalidArgumentException;
use Illuminate\Support\Collection;

abstract class Group implements Constrainer
{
    /**
     * The list of constraints.
     *
     * @var \Illuminate\Support\Collection<\Silber\Bouncer\Constraints\Constrainer>
     */
    protected $constraints;

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
     * Create a new instance from the raw data.
     *
     * @param  array  $data
     * @return static
     */
    public static function fromData(array $data)
    {
        return new static(array_map(function ($data) {
            return $data['class']::fromData($data['params']);
        }, $data['constraints']));
    }

    /**
     * Create a new instance for this given logical operator.
     *
     * @param  string  $data
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public static function ofType($logicalOperator)
    {
        if (! in_array($logicalOperator, ['and', 'or'])) {
            throw new InvalidArgumentException(
                "{$logicalOperator} is an invalid logical operator"
            );
        }

        return $logicalOperator == 'and' ? new AndGroup : new OrGroup;
    }

    /**
     * Checks whether the given instance's logical type
     * is that of the given logical operator.
     *
     * @param  string  $logicalOperator
     * @return bool
     */
    abstract public function isOfType($logicalOperator);

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
                'constraints' => $this->constraints->map(function ($constraint) {
                    return $constraint->data();
                })->all(),
            ],
        ];
    }

    /**
     * Returns either the single constrainer, or the group itself if there are multiple.
     *
     * @return \Silber\Bouncer\Constraints\Constrainer
     */
    public function unwrapIfSingle()
    {
        return $this->isSingle() ? $this->constraints->first() : $this;
    }

    /**
     * Get the list of constraints.
     *
     * @return array
     */
    public function constraints()
    {
        return $this->constraints;
    }

    /**
     * Determine whether the constraints list is empty.
     *
     * @return array
     */
    public function isEmpty()
    {
        return $this->constraints->isEmpty();
    }

    /**
     * Determine whether the group only has a single constraint.
     *
     * @return array
     */
    public function isSingle()
    {
        return $this->constraints->count() == 1;
    }
}
