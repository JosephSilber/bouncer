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
        return $this->constraints->every(
            function ($constraint) use ($entity, $authority) {
                return $constraint->check($entity, $authority);
            }
        );
    }
}
