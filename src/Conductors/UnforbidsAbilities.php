<?php

namespace Silber\Bouncer\Conductors;

class UnforbidsAbilities
{
    use Concerns\DisassociatesAbilities;

    /**
     * The authority from which to remove the forbiddal.
     *
     * @var \Illuminate\Database\Eloquent\Model|string|null
     */
    protected $authority;

    /**
     * The constraints to use for the detach abilities query.
     *
     * @var array
     */
    protected $constraints = ['forbidden' => true];

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model|string|null  $authority
     */
    public function __construct($authority = null)
    {
        $this->authority = $authority;
    }
}
