<?php

namespace Silber\Bouncer\Conductors;

class RemovesAbilities
{
    use Concerns\DisassociatesAbilities;

    /**
     * The authority from which to remove abilities.
     *
     * @var \Illuminate\Database\Eloquent\Model|string
     */
    protected $authority;

    /**
     * The constraints to use for the detach abilities query.
     *
     * @var array
     */
    protected $constraints = ['forbidden' => false];

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model|string  $authority
     */
    public function __construct($authority)
    {
        $this->authority = $authority;
    }
}
