<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Concerns\Authorizable;
use Silber\Bouncer\Database\HasRolesAndAbilities;

class User extends Model
{
    use Authorizable, HasRolesAndAbilities;

    protected $guarded = [];
}
