<?php

namespace Silber\Bouncer\Constraints;

use Illuminate\Database\Eloquent\Model;

interface Constrainer
{
    /**
     * Create a new instance from the raw data.
     *
     * @param  array  $data
     * @return static
     */
    public static function fromData(array $data);

    /**
     * Get the JSON-able data of this object.
     *
     * @return array
     */
    public function data();

    /**
     * Determine whether the given entity/authority passes this constraint.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @param  \Illuminate\Database\Eloquent\Model|null  $authority
     * @return bool
     */
    public function check(Model $entity, Model $authority = null);

    /**
     * Set the logical operator to use when checked after a previous constrainer.
     *
     * @param  string|null  $operator
     * @return $this|string
     */
    public function logicalOperator($operator = null);

    /**
     * Checks whether the logical operator is an "and" operator.
     *
     * @param string  $operator
     */
    public function isAnd();

    /**
     * Checks whether the logical operator is an "and" operator.
     *
     * @param string  $operator
     */
    public function isOr();

    /**
     * Determine whether the given constrainer is equal to this object.
     *
     * @param  \Silber\Bouncer\Constraints\Constrainer  $constrainer
     * @return bool
     */
    public function equals(Constrainer $constrainer);
}
