<?php

namespace Silber\Bouncer\Database;

trait HasRolesAndAbilities
{
    use HasRoles, HasAbilities {
        HasRoles::getClipboardInstance insteadof HasAbilities;
    }
}
