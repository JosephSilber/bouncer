<?php

namespace Silber\Bouncer\Database;

use Silber\Bouncer\Database\Concerns\HasAbilities;
use Silber\Bouncer\Database\Concerns\HasRoles;

trait HasRolesAndAbilities
{
    use HasAbilities, HasRoles;
}
