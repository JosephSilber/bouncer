<?php

namespace Silber\Bouncer\Constraints;

use Illuminate\Database\Eloquent\Model;

interface Constrainer
{
    /**
     * Determine whether the given entity/authority passes the constraint.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @param  \Illuminate\Database\Eloquent\Model|null  $authority
     * @return bool
     */
    public function check(Model $entity, Model $authority = null);

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
}
