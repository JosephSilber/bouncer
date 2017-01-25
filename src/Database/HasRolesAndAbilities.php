<?php

namespace Silber\Bouncer\Database;

use Silber\Bouncer\Database\Concerns\HasRoles;
use Silber\Bouncer\Database\Concerns\HasAbilities;

trait HasRolesAndAbilities
{
    use HasRoles, HasAbilities {
        HasRoles::getClipboardInstance insteadof HasAbilities;
    }
}
