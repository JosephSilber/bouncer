<?php

namespace Silber\Bouncer\Database;

use Illuminate\Database\Eloquent\Model;

class Ability extends Model
{
    use IsAbility;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];
}
