<?php

namespace Silber\Bouncer\Constraints;

use Illuminate\Database\Eloquent\Model;

class AndGroup extends Group
{
    /**
     * Determine whether the given entity/authority passes the constraints.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @param  \Illuminate\Database\Eloquent\Model|null  $authority
     * @return bool
     */
    public function check(Model $entity, Model $authority = null)
    {
        foreach ($this->constraints as $constraint) {
            if (! $constraint->check($entity, $authority)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks whether the given instance's logical type
     * is that of the given logical operator.
     *
     * @param  string  $logicalOperator
     * @return bool
     */
    public function isOfType($logicalOperator)
    {
        return $logicalOperator == 'and';
    }
}
