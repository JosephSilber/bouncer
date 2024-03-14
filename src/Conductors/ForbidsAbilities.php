<?php

namespace Silber\Bouncer\Conductors;

class ForbidsAbilities
{
    use Concerns\AssociatesAbilities;

    /**
     * The authority to be forbidden from the abilities.
     *
     * @var \Illuminate\Database\Eloquent\Model|string|null
     */
    protected $authority;

    /**
     * Whether the associated abilities should be forbidden abilities.
     *
     * @var bool
     */
    protected $forbidding = true;

    /**
     * Constructor.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $authority
     */
    public function __construct($authority = null)
    {
        $this->authority = $authority;
    }
}
