<?php

namespace Silber\Bouncer\Constraints;

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
    public function __construct($constraints)
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
}
