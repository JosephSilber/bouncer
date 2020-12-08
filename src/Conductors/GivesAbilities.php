<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Models;
use Illuminate\Database\Eloquent\Model;

class GivesAbilities
{
    use Concerns\AssociatesAbilities;

    /**
     * The authority to be given abilities.
     *
     * @var \Illuminate\Database\Eloquent\Model|string|null
     */
    protected $authority;

    /**
     * Whether the associated abilities should be forbidden abilities.
     *
     * @var bool
     */
    protected $forbidding = false;

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
