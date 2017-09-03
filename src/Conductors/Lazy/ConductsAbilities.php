<?php

namespace Silber\Bouncer\Conductors\Lazy;

class ConductsAbilities
{
    /**
     * The conductor handling the permission.
     *
     * @var \Silber\Bouncer\Conductors\Concerns\ConductsAbilities
     */
    protected $conductor;

    /**
     * The abilities to which ownership is restricted.
     *
     * @var string|string[]
     */
    protected $abilities;

    /**
     * Determines whether the given abilities should be granted on all models.
     *
     * @var bool
     */
    protected $everything = false;

    /**
     * The extra attributes for the abilities.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Constructor.
     *
     * @param \Silber\Bouncer\Conductors\Concerns\ConductsAbilities  $conductor
     * @param mixed  $model
     * @param array  $attributes
     */
    public function __construct($conductor, $abilities)
    {
        $this->conductor = $conductor;
        $this->abilities = $abilities;
    }

    /**
     * Sets that the abilities should be applied towards everything.
     *
     * @param  array  $attributes
     * @return void
     */
    public function everything(array $attributes = [])
    {
        $this->everything = true;

        $this->attributes = $attributes;
    }

    /**
     * Destructor.
     *
     */
    public function __destruct()
    {
        $this->conductor->to(
            $this->abilities,
            $this->everything ? '*' : null,
            $this->attributes
        );
    }
}
