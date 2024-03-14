<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class UserWithSoftDeletes extends User
{
    use SoftDeletes;

    public $table = 'users';
}