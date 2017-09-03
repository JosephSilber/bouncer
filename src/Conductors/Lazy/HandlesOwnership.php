<?php

namespace Silber\Bouncer\Conductors\Lazy;

class HandlesOwnership
{
    /**
     * The conductor handling the permission.
     *
     * @var \Silber\Bouncer\Conductors\Concerns\ConductsAbilities
     */
    protected $conductor;

    /**
     * The model to be owned.
     *
     * @var string|object
     */
    protected $model;

    /**
     * The extra attributes for the ability.
     *
     * @var array
     */
    protected $attributes;

    /**
     * The abilities to which ownership is restricted.
     *
     * @var string|string[]
     */
    protected $ability = '*';

    /**
     * Constructor.
     *
     * @param \Silber\Bouncer\Conductors\Concerns\ConductsAbilities  $conductor
     * @param string|object  $model
     * @param array  $attributes
     */
    public function __construct($conductor, $model, array $attributes = [])
    {
        $this->conductor = $conductor;
        $this->model = $model;
        $this->attributes = $attributes;
    }

    /**
     * Limit ownership to the given ability.
     *
     * @param  string|string[]  $ability
     * @param  array  $attributes
     * @return void
     */
    public function to($ability, array $attributes = [])
    {
        $this->ability = $ability;

        $this->attributes = array_merge($this->attributes, $attributes);
    }

    /**
     * Destructor.
     *
     */
    public function __destruct()
    {
        $this->conductor->to(
            $this->ability,
            $this->model,
            $this->attributes + ['only_owned' => true]
        );
    }
}
