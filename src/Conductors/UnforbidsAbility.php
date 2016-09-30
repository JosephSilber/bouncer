<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Conductors\Traits\ConductsAbilities;
use Silber\Bouncer\Conductors\Traits\DisassociatesAbilities;

use Illuminate\Database\Eloquent\Model;

class UnforbidsAbility
{
    use ConductsAbilities, DisassociatesAbilities;

    /**
     * The model from which to remove the forbiddal.
     *
     * @var \Illuminate\Database\Eloquent\Model|string
     */
    protected $model;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model|string  $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Detach the given ability IDs from the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $ids
     * @return void
     */
    protected function detachAbilities(Model $model, array $ids)
    {
        $this->detachAbilitiesWithPivotConstraints($model, $ids, [
            'forbidden' => true,
        ]);
    }
}
