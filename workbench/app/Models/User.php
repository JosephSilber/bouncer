<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\HasRolesAndAbilities;
use Silber\Bouncer\Database\Concerns\Authorizable;

class User extends Model
{
    use Authorizable, HasRolesAndAbilities;

    protected $guarded = [];
}